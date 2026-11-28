<?php

namespace App\Services\AiAssistant\Tools;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Base class for the assistant's tools.
 *
 * Every tool runs as the signed-in user: it checks that user's permission and validates its
 * arguments before doing anything, and answers the model with JSON it can relay to the user.
 */
abstract class AssistantTool implements Tool
{
    /**
     * The permission the user needs to run this tool, or null when any signed-in user may run it.
     */
    abstract protected function permission(): ?string;

    /**
     * Run the tool with validated arguments and return the data to give back to the model.
     *
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    abstract protected function run(array $arguments): array;

    /**
     * The validation rules for the tool's arguments.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [];
    }

    /**
     * Prepare the raw arguments before validation.
     *
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    protected function prepareArguments(array $arguments): array
    {
        return $arguments;
    }

    /**
     * The tool's name as the model sees it, such as "create_product".
     */
    public function name(): string
    {
        return Str::snake(class_basename($this));
    }

    public function handle(Request $request): Stringable|string
    {
        if (! $this->isPermitted()) {
            return $this->toJson([
                'error' => 'permission_denied',
                'message' => "The current user does not have the \"{$this->permission()}\" permission, so this action was not performed. Tell the user they need this permission from an administrator.",
            ]);
        }

        $validator = $this->validator($request);

        if ($validator->fails()) {
            return $this->toJson([
                'error' => 'validation_failed',
                'message' => 'The arguments are invalid. Fix them and try again, or ask the user for the missing information.',
                'errors' => $validator->errors()->toArray(),
            ]);
        }

        try {
            return $this->toJson($this->run($validator->validated()));
        } catch (ValidationException $exception) {
            return $this->toJson([
                'error' => 'validation_failed',
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ]);
        }
    }

    /**
     * Determine whether the signed-in user may run this tool.
     */
    protected function isPermitted(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        $permission = $this->permission();

        return $permission === null || $user->isAppAdmin() || $user->can($permission);
    }

    protected function validator(Request $request): \Illuminate\Validation\Validator
    {
        return Validator::make($this->prepareArguments($request->all()), $this->rules());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function toJson(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
