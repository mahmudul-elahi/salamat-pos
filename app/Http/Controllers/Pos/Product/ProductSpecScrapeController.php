<?php

namespace App\Http\Controllers\Pos\Product;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Models\Pos\Product\Product;
use App\Models\Pos\ProductSpecification\ProductSpecification;
use App\Models\Pos\SpecificationGroup\SpecificationGroup;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ProductSpecScrapeController extends Controller
{
    public function scrape(Request $request)
    {
        $request->validate([
            'url' => ['required', 'url'],
        ]);

        $url = $request->input('url');

        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'])
                ->get($url);

            if (!$response->successful()) {
                throw new GeneralException('Failed to fetch the URL. Status: ' . $response->status(), 422);
            }

            $html = $response->body();
            $specs = $this->parseSpecs($html);

            if (empty($specs)) {
                throw new GeneralException('No specification data found on this page.', 422);
            }

            return response()->json(['specs' => $specs]);
        } catch (GeneralException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new GeneralException('Could not scrape the page: ' . $e->getMessage(), 422);
        }
    }

    public function saveSpecs(Request $request, Product $product)
    {
        $request->validate([
            'specs'              => 'required|array|min:1',
            'specs.*.group'      => 'required|string|max:255',
            'specs.*.sub'        => 'nullable|string|max:255',
            'specs.*.value'      => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->input('specs') as $spec) {
                $groupName = trim($spec['group']);
                $subName   = isset($spec['sub']) ? trim($spec['sub']) : null;
                $value     = trim($spec['value']);

                // Find or create parent group
                $parentGroup = SpecificationGroup::firstOrCreate(
                    ['name' => $groupName, 'parent_id' => null]
                );

                $subGroupId = null;
                if ($subName) {
                    $subGroup   = SpecificationGroup::firstOrCreate(
                        ['name' => $subName, 'parent_id' => $parentGroup->id]
                    );
                    $subGroupId = $subGroup->id;
                }

                ProductSpecification::create([
                    'product_id' => $product->id,
                    'parent_id'  => $parentGroup->id,
                    'sub_id'     => $subGroupId,
                    'details'    => $value,
                ]);
            }

            DB::commit();

            return successResponse('Specifications saved successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            throw new GeneralException($e->getMessage(), 500);
        }
    }

    private function parseSpecs(string $html): array
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $specs = [];

        // Strategy 1: definition lists <dl><dt>key</dt><dd>value</dd></dl>
        $dls = $xpath->query('//dl');
        if ($dls->length > 0) {
            foreach ($dls as $dl) {
                $currentGroup = 'General';
                $dts = $xpath->query('.//dt', $dl);
                $dds = $xpath->query('.//dd', $dl);

                for ($i = 0; $i < $dts->length; $i++) {
                    $key   = trim($dts->item($i)->textContent);
                    $value = $dds->item($i) ? trim($dds->item($i)->textContent) : '';
                    if ($key && $value) {
                        $specs[] = ['group' => $currentGroup, 'sub' => $key, 'value' => $value];
                    }
                }
            }
            if (!empty($specs)) return $specs;
        }

        // Strategy 2: tables with section headers (th colspan spanning a row = group name)
        $tables = $xpath->query('//table');
        foreach ($tables as $table) {
            $rows = $xpath->query('.//tr', $table);
            $currentGroup = 'General';

            foreach ($rows as $row) {
                $ths = $xpath->query('.//th', $row);
                $tds = $xpath->query('.//td', $row);

                // Full-width th = group header
                if ($ths->length === 1 && $tds->length === 0) {
                    $currentGroup = trim($ths->item(0)->textContent);
                    continue;
                }

                // th + td = key-value pair
                if ($ths->length === 1 && $tds->length === 1) {
                    $key   = trim($ths->item(0)->textContent);
                    $value = trim($tds->item(0)->textContent);
                    if ($key && $value) {
                        $specs[] = ['group' => $currentGroup, 'sub' => $key, 'value' => $value];
                    }
                    continue;
                }

                // 2-column td/td = key-value pair
                if ($tds->length === 2) {
                    $key   = trim($tds->item(0)->textContent);
                    $value = trim($tds->item(1)->textContent);
                    if ($key && $value) {
                        $specs[] = ['group' => $currentGroup, 'sub' => $key, 'value' => $value];
                    }
                }
            }
        }
        if (!empty($specs)) return $specs;

        // Strategy 3: heading + sibling table (h2/h3 before a table)
        $headings = $xpath->query('//h2|//h3|//h4');
        foreach ($headings as $heading) {
            $groupName = trim($heading->textContent);
            $sibling   = $heading->nextSibling;

            while ($sibling && $sibling->nodeType !== XML_ELEMENT_NODE) {
                $sibling = $sibling->nextSibling;
            }

            if ($sibling && strtolower($sibling->nodeName) === 'table') {
                $rows = $xpath->query('.//tr', $sibling);
                foreach ($rows as $row) {
                    $tds = $xpath->query('.//td', $row);
                    if ($tds->length >= 2) {
                        $key   = trim($tds->item(0)->textContent);
                        $value = trim($tds->item(1)->textContent);
                        if ($key && $value) {
                            $specs[] = ['group' => $groupName, 'sub' => $key, 'value' => $value];
                        }
                    }
                }
            }
        }

        return $specs;
    }
}
