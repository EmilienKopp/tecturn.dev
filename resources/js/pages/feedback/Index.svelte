<script module lang="ts">
    import { create } from '@/routes/feedback';

    export const layout = {
        breadcrumbs: [{ title: 'Feedback', href: create().url }],
    };
</script>

<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { store } from '@/routes/feedback';

    const form = useForm({ message: '' });

    function submit(event: SubmitEvent): void {
        event.preventDefault();

        form.post(store.url(), {
            preserveScroll: true,
            onSuccess: () => form.reset('message'),
        });
    }
</script>

<AppHead title="Feedback" />

<div class="mx-auto flex w-full max-w-2xl flex-col gap-8 p-6">
    <Heading
        variant="small"
        title="Feedback"
        description="Found a bug, missing a feature, or just want to tell us something? We read every message."
    />

    <form class="flex flex-col gap-4" onsubmit={submit}>
        <div class="flex flex-col gap-2">
            <textarea
                bind:value={form.message}
                rows={8}
                placeholder="What's on your mind?"
                class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                data-test="feedback-message"
            ></textarea>
            <InputError message={form.errors.message} />
        </div>

        <div class="flex justify-end">
            <Button
                type="submit"
                disabled={form.processing || form.message.trim() === ''}
                data-test="feedback-submit"
            >
                Send feedback
            </Button>
        </div>
    </form>
</div>
