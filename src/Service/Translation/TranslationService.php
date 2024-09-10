<?php

declare(strict_types=1);

namespace App\Service\Translation;

use App\Contract\Multilingual;

class TranslationService
{
    private array $translations = [];
    private string $path;

    public function __construct(Multilingual $multilingual)
    {
        $this->path = $multilingual->translationPath();
    }

    public function setLanguage(string $locale): self
    {
        $file = $this->path . "$locale.php";
        if (file_exists($file)) {
            $this->translations = include $file;
        } else {
            $this->translations = include $this->defaultLanguage();
        }

        return $this;
    }

    public function trans(string $key): ?string
    {
        return $this->translations[$key] ?? null;
    }

    public function defaultLanguage(): string
    {
        return $this->path . 'en.php';
    }
}