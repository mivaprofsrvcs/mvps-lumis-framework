<x-lumis-exceptions-renderer::layout :$exception>
    <div class="renderer container mx-auto lg:px-8">
        <x-lumis-exceptions-renderer::navigation :$exception />

        <main class="px-6 pb-12 pt-6">
            <div class="container mx-auto">
                <x-lumis-exceptions-renderer::header :$exception />

                <x-lumis-exceptions-renderer::trace-and-editor :$exception />

                <x-lumis-exceptions-renderer::context :$exception />
            </div>
        </main>
    </div>
</x-lumis-exceptions-renderer::layout>
