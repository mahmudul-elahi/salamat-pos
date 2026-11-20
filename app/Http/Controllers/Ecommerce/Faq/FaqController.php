<?php

namespace App\Http\Controllers\Ecommerce\Faq;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ecommerce\Faq\FaqRequest;
use App\Models\Ecommerce\Faq\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index(Request $request)
    {
        return Faq::query()
            ->with(['tenant:id,name'])
            ->whereColumn('id', 'group_id')
            ->when($request->search, function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('question', 'like', '%' . $request->search . '%')
                        ->orWhere('answer', 'like', '%' . $request->search . '%');
                });
            })
            ->orderBy('order')
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 10));
    }

    public function store(FaqRequest $request)
    {
        $common = [
            'tenant_id' => $request->tenant_id,
            'order'     => $request->order ?? 0,
            'status'    => $request->status,
        ];

        $firstFaq = null;

        foreach ($request->faqs as $faqData) {
            $faq = Faq::create(array_merge($common, [
                'lang'     => $faqData['lang'],
                'question' => $faqData['question'],
                'answer'   => $faqData['answer'],
                'group_id' => $firstFaq?->id ?? 0,
            ]));

            if (!$firstFaq) {
                $firstFaq = $faq;
                $faq->update(['group_id' => $faq->id]);
            }
        }

        return successResponse('FAQ created successfully');
    }

    public function show(Faq $faq)
    {
        $groupId = $faq->group_id;

        $translations = Faq::where('group_id', $groupId)
            ->get(['id', 'lang', 'question', 'answer']);

        return response()->json([
            'tenant_id'       => $faq->tenant_id,
            'faq_category_id' => $faq->faq_category_id,
            'order'           => $faq->order,
            'status'          => $faq->status,
            'faqs'            => $translations,
        ]);
    }

    public function update(FaqRequest $request, Faq $faq)
    {
        $groupId = $faq->group_id;

        $common = [
            'tenant_id' => $request->tenant_id,
            'order'     => $request->order ?? 0,
            'status'    => $request->status,
        ];

        foreach ($request->faqs as $faqData) {
            if (!empty($faqData['id'])) {
                // ->update() on an instance (not the query builder) so the
                // saved event fires and RevalidatesFrontendTrait triggers.
                Faq::find($faqData['id'])?->update(array_merge($common, [
                    'question' => $faqData['question'],
                    'answer'   => $faqData['answer'],
                ]));
            } else {
                Faq::create(array_merge($common, [
                    'lang'     => $faqData['lang'],
                    'question' => $faqData['question'],
                    'answer'   => $faqData['answer'],
                    'group_id' => $groupId,
                ]));
            }
        }

        return successResponse('FAQ updated successfully');
    }

    public function destroy(Faq $faq)
    {
        // Delete each instance (not the query builder) so the deleted event
        // fires and RevalidatesFrontendTrait triggers.
        Faq::where('group_id', $faq->group_id)->get()->each->delete();

        return successResponse('FAQ deleted successfully');
    }
}
