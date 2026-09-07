namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ChatbotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // معالجة إرسال question أو message
        if ($this->filled('message') && ! $this->filled('question')) {
            $this->merge(['question' => $this->input('message')]);
        }
    }

    public function rules(): array
    {
        return [
            'question'        => ['required', 'string', 'max:1000'],
            'conversation_id' => ['nullable', 'uuid'],
            'session_id'      => ['nullable', 'uuid'],
            'rating'          => ['nullable', 'integer', 'min:1', 'max:5'],
            'locale'          => ['nullable', 'string', 'in:ar,en'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // منع رفع الملفات في قيم السؤال
            foreach (['question', 'message'] as $key) {
                if ($this->hasFile($key)) {
                    $validator->errors()->add('question', 'Send the question as plain text, not as a file upload.');
                }
            }

            // منع إرسال المصفوفات
            if (is_array($this->input('question'))) {
                $validator->errors()->add('question', 'Multiple question values are not allowed.');
            }

            // التأكد من أن النص ليس فارغاً بعد التفريغ (trim)
            if (is_string($this->input('question')) && trim($this->input('question')) === '') {
                $validator->errors()->add('question', 'A non-empty question is required.');
            }
        });
    }
}