<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { toUrl } from '@/lib/utils';
    import { login } from '@/routes';
    import { store as storeBeta } from '@/routes/beta';
</script>

<AppHead title="Request beta access" />

<div
    class="house flex min-h-screen flex-col text-[hsl(40_30%_96%)] antialiased"
>
    <header
        class="mx-auto flex w-full max-w-5xl items-center justify-between px-6 py-4"
    >
        <Link
            href={toUrl('/')}
            class="font-display text-xl font-semibold tracking-tight select-none"
        >
            Tecturn<span class="text-[hsl(37_91%_55%)]">.</span>
        </Link>
        <Link
            href={toUrl(login())}
            class="rounded-md border border-[hsl(34_9%_22%)] px-4 py-1.5 text-sm text-[hsl(40_20%_86%)] transition-colors hover:border-[hsl(37_40%_35%)] focus-visible:ring-2 focus-visible:ring-[hsl(37_91%_55%)] focus-visible:outline-none"
        >
            Log in
        </Link>
    </header>

    <main class="flex grow flex-col items-center justify-center px-6 py-10">
        <div class="w-full max-w-md">
            <div class="mb-6 text-center">
                <h1 class="font-display text-3xl font-bold tracking-tight">
                    Request beta access
                </h1>
                <p class="mt-2 text-sm text-[hsl(40_15%_75%)]">
                    Tecturn is in private beta. Leave your details and we'll let
                    you in as spots open up.
                </p>
            </div>

            <div
                class="rounded-xl border border-[hsl(34_9%_18%)] bg-[hsl(36_11%_10%)] p-6 shadow-xl"
            >
                <Form {...storeBeta.form()} class="grid gap-4" resetOnSuccess>
                    {#snippet children({ errors, processing })}
                        <div class="grid gap-2">
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                name="name"
                                type="text"
                                placeholder="Ada Lovelace"
                                required
                                data-test="beta-name"
                                class="text-accent-foreground"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div class="grid gap-2">
                            <Label for="email">Email address</Label>
                            <Input
                                id="email"
                                name="email"
                                type="email"
                                placeholder="you@example.com"
                                required
                                data-test="beta-email"
                                class="text-accent-foreground"
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div class="grid gap-2">
                            <Label for="message">
                                What would you use it for?
                                <span class="text-[hsl(37_6%_55%)]"
                                    >(optional)</span
                                >
                            </Label>
                            <textarea
                                id="message"
                                name="message"
                                rows="4"
                                placeholder="Tell us a little about your talks."
                                class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                                data-test="beta-message"
                            ></textarea>
                            <InputError message={errors.message} />
                        </div>

                        <Button
                            type="submit"
                            disabled={processing}
                            class="mt-2 bg-[hsl(37_91%_55%)] font-medium text-[hsl(36_45%_10%)] hover:bg-[hsl(37_91%_62%)]"
                            data-test="beta-submit"
                        >
                            {processing ? 'Sending…' : 'Request access'}
                        </Button>
                    {/snippet}
                </Form>
            </div>
        </div>
    </main>
</div>

<style>
    .house {
        background-color: hsl(36 11% 7%);
        background-image: radial-gradient(
            120% 60% at 50% -10%,
            hsl(37 60% 30% / 0.2),
            transparent 65%
        );
    }
</style>
