<x-lumis-exceptions-renderer::card class="mt-6 overflow-x-auto">
    <div
        x-data="{
            includeVendorFrames: false,
            index: {{ $exception->defaultFrame() }},
        }"
    >
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3" x-clock>
            <x-lumis-exceptions-renderer::trace :$exception />
            <x-lumis-exceptions-renderer::editor :$exception />
        </div>
    </div>
</x-lumis-exceptions-renderer::card>
