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
        recordNavigation = false,
    }: {
        source: PresentationSource;
        sourcePdfUrl: string | null;
        // Reports (0-based current index, total) so the dock can show the slide
        // number and pace the deck, and so a rehearsal records each move.
        onPageChange?: (current: number, total: number) => void;
        // Only during a rehearsal do we take over Google Slides navigation (to
        // record the slide pages for replay). The reload-per-step it needs is a
        // poor live experience, so live/test decks just embed the frame and let
        // the presenter drive it natively — no counter, no intercepted events.
        recordNavigation?: boolean;
    } = $props();

    // --- Google Slides ---------------------------------------------------
    // A Slides iframe can't be introspected or key-driven across origins. So when
    // recording, the presenter declares how many slides it has, and we own
    // navigation: a capture overlay + our keyboard handler advance the dock
    // counter and reload the embed to the target slide — the only lever a parent
    // page has over a cross-origin embed.
    const embedUrl = $derived(googleSlidesEmbedUrl(source.externalUrl));
    const slideCount = $derived(source.slideCount ?? 0);
    // Take over Slides only while recording and only if a count was declared.
    const drivesSlides = $derived(
        source.type === 'google_slides' && recordNavigation && slideCount > 0,
    );
    let slideIndex = $state(0);

    // Reloading the iframe with a 1-based `slide` param jumps Google to that slide.
    const slidesSrc = $derived(
        embedUrl === null ? null : `${embedUrl}&slide=${slideIndex + 1}`,
    );

    const slidesGoTo = (index: number): void => {
        if (
            index < 0 ||
            (slideCount > 0 && index > slideCount - 1) ||
            index === slideIndex
        ) {
            return;
        }

        slideIndex = index;
        onPageChange?.(slideIndex, slideCount);
    };

    const slidesNext = (): void => slidesGoTo(slideIndex + 1);
    const slidesPrev = (): void => slidesGoTo(slideIndex - 1);

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
        const forward = ['ArrowRight', 'PageDown', ' ', 'Enter'].includes(
            event.key,
        );
        const backward = ['ArrowLeft', 'PageUp', 'Backspace'].includes(
            event.key,
        );

        if (!forward && !backward) {
            return;
        }

        if (source.type === 'pdf') {
            event.preventDefault();

            if (forward) {
                next();
            } else {
                prev();
            }
        } else if (drivesSlides) {
            event.preventDefault();

            if (forward) {
                slidesNext();
            } else {
                slidesPrev();
            }
        }
        // Otherwise (live/test Google Slides) we intercept nothing — the frame
        // handles its own keys.
    };

    // PDF always uses our keyboard; Google Slides only while recording.
    onMount(() => {
        window.addEventListener('keydown', onKeydown);

        if (drivesSlides) {
            onPageChange?.(0, slideCount);
        }

        return () => window.removeEventListener('keydown', onKeydown);
    });

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

        return () => {
            disposed = true;
            resizeObserver?.disconnect();
            renderTask?.cancel();
            pdfDoc?.destroy();
        };
    });
</script>

{#if source.type === 'google_slides'}
    {#if embedUrl}
        <div class="group relative h-full w-full">
            <iframe
                src={drivesSlides ? slidesSrc : embedUrl}
                title="Google Slides presentation"
                class="h-full w-full border-0"
                allow="fullscreen"
                allowfullscreen
                data-test="external-google-slides"
            ></iframe>

            {#if drivesSlides}
                <!-- Capture layer: keeps focus on our side (so keyboard reaches
                     us, not the frame) and advances on click, like Slides does. -->
                <button
                    type="button"
                    class="absolute inset-0 h-full w-full cursor-pointer bg-transparent"
                    onclick={slidesNext}
                    aria-label="Next slide"
                    data-test="external-slides-advance"
                ></button>

                <button
                    type="button"
                    class="absolute left-3 top-1/2 -translate-y-1/2 rounded-full bg-black/40 p-2 text-white opacity-0 transition-opacity hover:bg-black/60 focus-visible:opacity-100 disabled:cursor-not-allowed disabled:opacity-0 [.group:hover_&]:opacity-100"
                    onclick={slidesPrev}
                    disabled={slideIndex <= 0}
                    aria-label="Previous slide"
                    data-test="external-slides-prev"
                >
                    <ChevronLeft class="h-6 w-6" />
                </button>
                <button
                    type="button"
                    class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full bg-black/40 p-2 text-white opacity-0 transition-opacity hover:bg-black/60 focus-visible:opacity-100 disabled:cursor-not-allowed disabled:opacity-0 [.group:hover_&]:opacity-100"
                    onclick={slidesNext}
                    disabled={slideIndex >= slideCount - 1}
                    aria-label="Next slide"
                    data-test="external-slides-next"
                >
                    <ChevronRight class="h-6 w-6" />
                </button>
            {/if}
        </div>
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
