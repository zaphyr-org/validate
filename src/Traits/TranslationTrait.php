<?php

declare(strict_types=1);

namespace Zaphyr\Validate\Traits;

use Zaphyr\Translate\Translator;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
trait TranslationTrait
{
    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var string
     */
    protected $namespace;

    /**
     * @var string
     */
    private $internalNamespace = 'validation';

    /**
     * @param string|null $locale
     * @param string|null $directory
     * @param string|null $namespace
     *
     * @return void
     */
    protected function initTranslatorInstance(
        string $locale = null,
        string $directory = null,
        string $namespace = null
    ): void {
        $directories = [dirname(__DIR__, 2) . '/resources/translations'];

        if ($directory) {
            $directories[] = $directory;
        }

        $this->namespace = $namespace ?? $this->internalNamespace;
        $this->translator = new Translator($directories, $locale ?? 'en', 'en', Translator::READER_JSON);
    }

    /**
     * @param string $id
     *
     * @return string|null
     */
    protected function getTranslation(string $id): ?string
    {
        $customId = "$this->namespace.$id";

        if ($this->translator->has($customId) && is_string($value = $this->translator->get($customId))) {
            return $value;
        }

        $internalId = "$this->internalNamespace.$id";

        if ($this->translator->has($internalId) && is_string($value = $this->translator->get($internalId))) {
            return $value;
        }

        return null;
    }
}
