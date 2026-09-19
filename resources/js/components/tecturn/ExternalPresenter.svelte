<script lang="ts">
    import ChevronLeft from 'lucide-svelte/icons/chevron-left';
    import ChevronRight from 'lucide-svelte/icons/chevron-right';
    // The worker ships as an ESM asset; Vite resolves this to a hashed URL.
    import type { PDFDocumentProxy, RenderTask } from 'pdfjs-dist';
    import workerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url';
    import { onMount } from 'svelte';
    import { googleSlidesEmbedUrl } from '@/lib/tecturn/external-source';
    import type { PresentationSource } from '@/types/generated';

    let {
        source,
        sourcePdfUrl,
        onPageChange,
    }: {
        source: PresentationSource;
        sourcePdfUrl: string | null;
        // Reports (0-based current page, total pages) so the dock can pace the
        // deck. Google Slides can't be introspected across origins, so it never
        // fires and the dock hides its slide counter (total stays 0).
        onPageChange?: (current: number, total: number) => void;
    } = $props();

    // --- Google Slides ---------------------------------------------------
    const embedUrl = $derived(googleSlidesEmbedUrl(source.externalUrl));

    // --- PDF -------------------------------------------------------------
    let canvas = $state<HTMLCanvasElement>();
    let viewport = $state<HTMLDivElement>();
    let pageNum = $state(1);
    let numPages = $state(0);
    let loadError = $state(false);

    let pdfDoc: PDFDocumentProxy | null = null;
    let renderTask: RenderTask | null = null;

    const renderPage = async (num: number): Promise<void> => {
        if (!pdfDoc || !canvas || !viewport) {
            return;
        }

        // A page can be requested while the previous one is still painting;
        // cancel it so the canvas isn't left half-drawn.
        renderTask?.cancel();

        const page = await pdfDoc.getPage(num);
        const unscaled = page.getViewport({ scale: 1 });

        // Largest scale that keeps the whole page inside the 16:9 box, then
        // sharpened for the device's pixel density.
        const fit = Math.min(
            viewport.clientWidth / unscaled.width,
            viewport.clientHeight / unscaled.height,
        );
        const dpr = window.devicePixelRatio || 1;
        const scaled = page.getViewport({ scale: fit * dpr });

        canvas.width = scaled.width;
        canvas.height = scaled.height;
        canvas.style.width = `${scaled.width / dpr}px`;
        canvas.style.height = `${scaled.height / dpr}px`;

        const context = canvas.getContext('2d');

        if (!context) {
            return;
        }

        renderTask = page.render({ canvasContext: context, viewport: scaled });

        try {
            await renderTask.promise;
        } catch {
            // Cancelled renders throw; ignore them.
        }
    };

    const goTo = (num: number): void => {
        if (num < 1 || num > numPages || num === pageNum) {
            return;
        }

        pageNum = num;
        onPageChange?.(pageNum - 1, numPages);
        void renderPage(pageNum);
    };

    const next = (): void => goTo(pageNum + 1);
    const prev = (): void => goTo(pageNum - 1);

    const onKeydown = (event: KeyboardEvent): void => {
        if (['ArrowRight', 'PageDown', ' '].includes(event.key)) {
            event.preventDefault();
            next();
        } else if (['ArrowLeft', 'PageUp'].includes(event.key)) {
            event.preventDefault();
            prev();
        }
    };

    onMount(() => {
        if (source.type !== 'pdf' || !sourcePdfUrl) {
            return;
        }

        let disposed = false;
        let resizeObserver: ResizeObserver | null = null;

        // pdfjs touches browser-only globals, so it's imported lazily here
        // rather than at module load (which SSR would evaluate).
        (async () => {
            const pdfjs = await import('pdfjs-dist');
            pdfjs.GlobalWorkerOptions.workerSrc = workerUrl;

            try {
                pdfDoc = await pdfjs.getDocument(sourcePdfUrl).promise;
            } catch {
                loadError = true;

                return;
            }

            if (disposed) {
                pdfDoc.destroy();

                return;
            }

            numPages = pdfDoc.numPages;
            onPageChange?.(0, numPages);
            await renderPage(pageNum);

            // Re-fit the current page when the box resizes (window, dock toggle).
            let frame = 0;
            resizeObserver = new ResizeObserver(() => {
                cancelAnimationFrame(frame);
                frame = requestAnimationFrame(() => void renderPage(pageNum));
            });

            if (viewport) {
                resizeObserver.observe(viewport);
            }
        })();

        window.addEventListener('keydown', onKeydown);

        return () => {
            disposed = true;
            window.removeEventListener('keydown', onKeydown);
            resizeObserver?.disconnect();
            renderTask?.cancel();
            pdfDoc?.destroy();
        };
    });
</script>

{#if source.type === 'google_slides'}
    {#if embedUrl}
        <iframe
            src={embedUrl}
            title="Google Slides presentation"
            class="h-full w-full border-0"
            allow="fullscreen"
            allowfullscreen
            data-test="external-google-slides"
        ></iframe>
    {/if}
{:else if source.type === 'pdf'}
    <div
        bind:this={viewport}
        class="group relative flex h-full w-full items-center justify-center"
        data-test="external-pdf"
    >
        {#if loadError}
            <p class="text-sm text-zinc-400">Could not load the PDF.</p>
        {:else}
            <canvas bind:this={canvas} class="shadow-lg"></canvas>
        {/if}

        {#if numPages > 0 && !loadError}
            <button
                type="button"
                class="absolute left-3 top-1/2 -translate-y-1/2 rounded-full bg-black/40 p-2 text-white opacity-0 transition-opacity hover:bg-black/60 focus-visible:opacity-100 disabled:cursor-not-allowed disabled:opacity-0 [.group:hover_&]:opacity-100"
                onclick={prev}
                disabled={pageNum <= 1}
                aria-label="Previous page"
                data-test="external-pdf-prev"
            >
                <ChevronLeft class="h-6 w-6" />
            </button>
            <button
                type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full bg-black/40 p-2 text-white opacity-0 transition-opacity hover:bg-black/60 focus-visible:opacity-100 disabled:cursor-not-allowed disabled:opacity-0 [.group:hover_&]:opacity-100"
                onclick={next}
                disabled={pageNum >= numPages}
                aria-label="Next page"
                data-test="external-pdf-next"
            >
                <ChevronRight class="h-6 w-6" />
            </button>
        {/if}
    </div>
{/if}
