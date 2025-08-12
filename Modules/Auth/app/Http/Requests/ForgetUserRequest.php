<?php


namespace Modules\Auth\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class ForgetUserRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string','exists:users,phone'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
