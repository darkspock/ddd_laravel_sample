<?php

declare(strict_types=1);

namespace Apps\Shared\Http;

use Illuminate\Foundation\Http\FormRequest;

abstract class AbstractFormRequest extends FormRequest
{
    private ?FormRequestHelper $helper = null;

    public function getHelper(): FormRequestHelper
    {
        if ($this->helper === null) {
            $this->helper = new FormRequestHelper($this);
        }

        return $this->helper;
    }
}
