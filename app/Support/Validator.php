<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Small, explicit validation layer. Rules are intentionally simple strings
 * (`required`, `email`, `min:6`, `max:255`) so controllers read declaratively
 * and business logic stays out of the HTTP layer.
 */
final class Validator
{
    /** @var array<string, array<int, string>> */
    private array $errors = [];

    /**
     * @param array<string, mixed>            $data
     * @param array<string, array<int,string>> $rules field => [rule, rule...]
     */
    public function __construct(private array $data, private array $rules)
    {
    }

    public function passes(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $rules) {
            $value = $this->data[$field] ?? null;

            foreach ($rules as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $this->applyRule($field, $value, $name, $param);
            }
        }

        return $this->errors === [];
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    /** @return array<string, array<int, string>> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        foreach ($this->errors as $messages) {
            return $messages[0] ?? null;
        }

        return null;
    }

    private function applyRule(string $field, mixed $value, string $name, ?string $param): void
    {
        $valid = match ($name) {
            'required' => $value !== null && $value !== '',
            'email'    => $value === null || $value === '' || filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'min'      => $value === null || mb_strlen((string) $value) >= (int) $param,
            'max'      => $value === null || mb_strlen((string) $value) <= (int) $param,
            'numeric'  => $value === null || is_numeric($value),
            default    => true,
        };

        if (!$valid) {
            $this->errors[$field][] = $this->message($field, $name, $param);
        }
    }

    private function message(string $field, string $rule, ?string $param): string
    {
        return match ($rule) {
            'required' => "فیلد «{$field}» الزامی است.",
            'email'    => "فیلد «{$field}» باید یک ایمیل معتبر باشد.",
            'min'      => "فیلد «{$field}» باید حداقل {$param} کاراکتر باشد.",
            'max'      => "فیلد «{$field}» نباید بیش از {$param} کاراکتر باشد.",
            'numeric'  => "فیلد «{$field}» باید عددی باشد.",
            default    => "فیلد «{$field}» نامعتبر است.",
        };
    }
}
