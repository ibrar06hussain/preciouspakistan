@once
    <link rel="stylesheet" href="{{ asset('vendor/jodit/jodit.min.css') }}">
    <script src="{{ asset('vendor/jodit/jodit.min.js') }}" defer></script>
@endonce

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }},
            editor: null,
            init() {
                const mountEditor = () => {
                    if (typeof window.Jodit === 'undefined') {
                        setTimeout(mountEditor, 120);
                        return;
                    }
                    if (this.editor) {
                        return;
                    }
                    this.editor = window.Jodit.make(this.$refs.textarea, {
                        minHeight: {{ $getMinHeight() }},
                        toolbarAdaptive: false,
                        buttons: 'bold,italic,underline,|,paragraph,fontsize,brush,|,ul,ol,table,|,link,image,video,|,align,undo,redo,|,source,fullsize',
                        uploader: { insertImageAsBase64URI: false },
                        askBeforePasteHTML: false,
                        askBeforePasteFromWord: false,
                        defaultActionOnPaste: 'insert_clear_html',
                    });
                    this.editor.value = this.state ?? '';
                    this.editor.events.on('change', (newValue) => {
                        this.state = newValue;
                    });
                    this.$watch('state', (value) => {
                        if (this.editor && this.editor.value !== (value ?? '')) {
                            this.editor.value = value ?? '';
                        }
                    });
                };
                mountEditor();
            }
        }"
        wire:ignore
        class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white overflow-hidden"
    >
        <textarea x-ref="textarea" class="w-full min-h-[220px] p-3 text-sm"></textarea>
    </div>
    <p class="mt-1 text-xs text-gray-500">
        Self-hosted Jodit MIT editor. All HTML is sanitized server-side on save (scripts, PHP/Blade tags, and unsafe URLs are rejected).
    </p>
</x-dynamic-component>
