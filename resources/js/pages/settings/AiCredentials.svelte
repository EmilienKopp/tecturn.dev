<script module lang="ts">
    import { index } from '@/routes/ai-credentials';

    export const layout = {
        breadcrumbs: [
            {
                title: 'AI model settings',
                href: index(),
            },
        ],
    };
</script>

<script lang="ts">
    import { page, useForm, Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import {
        store,
        test,
        defaultMethod,
        destroy,
    } from '@/routes/ai-credentials';

    type CuratedModel = {
        driver: string;
        model: string;
        label: string;
        supportsSchema: boolean;
        base_url?: string | null;
    };

    type Credential = {
        id: number;
        label: string | null;
        driver: string;
        model: string;
        base_url: string | null;
        masked_key: string;
        is_default: boolean;
    };

    const props = $derived(
        page.props as unknown as {
            credentials: Credential[];
            curatedModels: CuratedModel[];
            freetextDrivers: string[];
            requiresBaseUrl: string[];
            house: { driver: string; model: string };
        },
    );

    let mode = $state<'curated' | 'custom'>('curated');
    let selectedCurated = $state(0);

    const form = useForm({
        label: '',
        driver: '',
        model: '',
        base_url: '',
        api_key: '',
        is_default: false,
    });

    // Keep driver/model/base_url in sync with the chosen curated model.
    function applyCurated(indexValue: number): void {
        const entry = props.curatedModels[indexValue];

        if (!entry) {
            return;
        }

        selectedCurated = indexValue;
        form.driver = entry.driver;
        form.model = entry.model;
        form.base_url = entry.base_url ?? '';
    }

    $effect(() => {
        if (mode === 'curated' && form.driver === '') {
            applyCurated(selectedCurated);
        }
    });

    const activeDriver = $derived(form.driver);
    const needsBaseUrl = $derived(props.requiresBaseUrl.includes(activeDriver));

    // A curated pick is verified for structured output; anything free-texted is not.
    const isUnverified = $derived(
        mode === 'custom' ||
            props.curatedModels[selectedCurated]?.supportsSchema === false,
    );

    let testState = $state<{
        status: 'idle' | 'running' | 'ok' | 'error';
        message: string;
    }>({ status: 'idle', message: '' });

    function readCookie(name: string): string {
        const match = document.cookie.match(
            new RegExp('(^|;\\s*)' + name + '=([^;]*)'),
        );

        return match ? decodeURIComponent(match[2]) : '';
    }

    async function runTest(): Promise<void> {
        testState = { status: 'running', message: '' };

        try {
            const response = await fetch(test.url(), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': readCookie('XSRF-TOKEN'),
                },
                body: JSON.stringify({
                    driver: form.driver,
                    model: form.model,
                    base_url: form.base_url,
                    api_key: form.api_key,
                }),
            });
            const data = await response.json();
            testState = {
                status: data.ok ? 'ok' : 'error',
                message: data.message ?? '',
            };
        } catch (error) {
            testState = {
                status: 'error',
                message:
                    error instanceof Error ? error.message : 'Request failed.',
            };
        }
    }

    function save(): void {
        form.post(store.url(), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                mode = 'curated';
                selectedCurated = 0;
                testState = { status: 'idle', message: '' };
            },
        });
    }
</script>

<AppHead title="AI model settings" />

<h1 class="sr-only">AI model settings</h1>

<div class="flex flex-col space-y-6">
    <Heading
        variant="small"
        title="AI models"
        description="Bring your own AI to generate decks. Add a provider API key and pick a model; it's used for your deck builds instead of the built-in model. Keys are encrypted and never shown again after saving."
    />

    {#if props.credentials.length > 0}
        <div class="flex flex-col gap-2">
            {#each props.credentials as credential (credential.id)}
                <div
                    class="flex items-center justify-between gap-4 rounded-md border p-3"
                    data-test="credential-{credential.id}"
                >
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="truncate font-medium">
                                {credential.label || credential.model}
                            </span>
                            {#if credential.is_default}
                                <span
                                    class="rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                                >
                                    Default
                                </span>
                            {/if}
                        </div>
                        <div class="truncate text-xs text-muted-foreground">
                            {credential.driver} · {credential.model} · key {credential.masked_key}
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        {#if !credential.is_default}
                            <Link
                                href={defaultMethod(credential.id)}
                                method="patch"
                                as="button"
                                class="text-sm text-muted-foreground hover:text-foreground"
                                preserveScroll
                            >
                                Make default
                            </Link>
                        {/if}
                        <Link
                            href={destroy(credential.id)}
                            method="delete"
                            as="button"
                            class="text-sm text-destructive hover:underline"
                            preserveScroll
                            data-test="delete-credential-{credential.id}"
                        >
                            Remove
                        </Link>
                    </div>
                </div>
            {/each}
        </div>
    {:else}
        <p class="text-sm text-muted-foreground">
            No AI credentials yet. Deck builds use the built-in {props.house
                .model} model.
        </p>
    {/if}

    <Heading variant="small" title="Add a model" description="" />

    <div class="flex gap-2">
        <Button
            type="button"
            variant={mode === 'curated' ? 'default' : 'outline'}
            size="sm"
            onclick={() => {
                mode = 'curated';
                applyCurated(selectedCurated);
            }}
        >
            Curated
        </Button>
        <Button
            type="button"
            variant={mode === 'custom' ? 'default' : 'outline'}
            size="sm"
            onclick={() => {
                mode = 'custom';
                form.driver = '';
                form.model = '';
                form.base_url = '';
            }}
        >
            Custom model
        </Button>
    </div>

    <div class="grid gap-4">
        {#if mode === 'curated'}
            <div class="grid gap-2">
                <Label for="curated-model">Model</Label>
                <select
                    id="curated-model"
                    class="rounded-md border bg-background px-3 py-2 text-sm"
                    value={String(selectedCurated)}
                    onchange={(event) =>
                        applyCurated(Number(event.currentTarget.value))}
                    data-test="curated-model"
                >
                    {#each props.curatedModels as model, i (model.label)}
                        <option value={String(i)}>{model.label}</option>
                    {/each}
                </select>
            </div>
        {:else}
            <div class="grid gap-2">
                <Label for="custom-driver">Provider</Label>
                <select
                    id="custom-driver"
                    class="rounded-md border bg-background px-3 py-2 text-sm"
                    value={form.driver}
                    onchange={(event) =>
                        (form.driver = event.currentTarget.value)}
                    data-test="custom-driver"
                >
                    <option value="" disabled>Choose a provider…</option>
                    {#each props.freetextDrivers as driver (driver)}
                        <option value={driver}>{driver}</option>
                    {/each}
                </select>
                <InputError message={form.errors.driver} />
            </div>

            <div class="grid gap-2">
                <Label for="custom-model">Model id</Label>
                <Input
                    id="custom-model"
                    bind:value={form.model}
                    placeholder="e.g. gpt-4o, claude-sonnet-4-6"
                    data-test="custom-model"
                />
                <InputError message={form.errors.model} />
            </div>
        {/if}

        {#if needsBaseUrl || mode === 'custom'}
            <div class="grid gap-2">
                <Label for="base-url">
                    Base URL
                    {#if needsBaseUrl}
                        <span class="text-xs text-muted-foreground"
                            >· required</span
                        >
                    {:else}
                        <span class="text-xs text-muted-foreground"
                            >· optional</span
                        >
                    {/if}
                </Label>
                <Input
                    id="base-url"
                    bind:value={form.base_url}
                    placeholder="https://api.example.com/v1"
                    data-test="base-url"
                />
                <InputError message={form.errors.base_url} />
            </div>
        {/if}

        <div class="grid gap-2">
            <Label for="api-key">API key</Label>
            <Input
                id="api-key"
                type="password"
                bind:value={form.api_key}
                autocomplete="off"
                placeholder="sk-…"
                data-test="api-key"
            />
            <InputError message={form.errors.api_key} />
        </div>

        <div class="grid gap-2">
            <Label for="label"
                >Label <span class="text-xs text-muted-foreground"
                    >· optional</span
                ></Label
            >
            <Input
                id="label"
                bind:value={form.label}
                placeholder="My OpenAI key"
                data-test="label"
            />
            <InputError message={form.errors.label} />
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input
                type="checkbox"
                bind:checked={form.is_default}
                data-test="is-default"
            />
            Use this model by default for new decks
        </label>

        {#if isUnverified}
            <p
                class="rounded-md border border-amber-500/40 bg-amber-500/10 p-3 text-sm text-amber-700 dark:text-amber-400"
                data-test="unverified-warning"
            >
                This model isn't on our verified list. Deck generation needs
                structured (JSON schema) output; if the model doesn't support
                it, builds will fail. Test the connection before relying on it.
            </p>
        {/if}

        {#if testState.status !== 'idle'}
            <p
                class="text-sm {testState.status === 'ok'
                    ? 'text-green-600 dark:text-green-400'
                    : testState.status === 'error'
                      ? 'text-destructive'
                      : 'text-muted-foreground'}"
                data-test="test-result"
            >
                {#if testState.status === 'running'}
                    Testing connection…
                {:else if testState.status === 'ok'}
                    Connection succeeded.
                {:else}
                    {testState.message}
                {/if}
            </p>
        {/if}

        <div class="flex items-center gap-3">
            <Button
                type="button"
                onclick={save}
                disabled={form.processing}
                data-test="save-credential">Save model</Button
            >
            <Button
                type="button"
                variant="outline"
                onclick={runTest}
                disabled={testState.status === 'running'}
                data-test="test-credential">Test connection</Button
            >
        </div>
    </div>
</div>
