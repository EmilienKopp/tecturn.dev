<script module lang="ts">
    import { edit } from '@/routes/profile';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Profile settings',
                href: edit(),
            },
        ],
    };
</script>

<script lang="ts">
    import { Form, page, useForm } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import DeleteUser from '@/components/DeleteUser.svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import ColorField from '@/components/tecturn/ColorField.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import {
        BRANDING_FALLBACK,
        BRANDING_KEYS,
        BRANDING_LABELS,
    } from '@/lib/tecturn/branding';
    import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
    import { update as updateBranding } from '@/routes/branding';

    const user = $derived(page.props.auth.user);

    const brandingForm = useForm({
        branding: { ...(page.props.auth.user.branding ?? BRANDING_FALLBACK) },
    });

    function saveBranding(): void {
        brandingForm.patch(updateBranding.url(), { preserveScroll: true });
    }

    function resetBranding(): void {
        for (const key of BRANDING_KEYS) {
            brandingForm.branding[key] = BRANDING_FALLBACK[key];
        }
    }
</script>

<AppHead title="Profile settings" />

<h1 class="sr-only">Profile settings</h1>

<div class="flex flex-col space-y-6">
    <Heading
        variant="small"
        title="Profile"
        description="Update your name, public handle, and social links"
    />

    <Form
        {...ProfileController.update.form()}
        class="space-y-6"
        options={{ preserveScroll: true }}
    >
        {#snippet children({ errors, processing })}
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    name="name"
                    class="mt-1 block w-full"
                    value={user.name}
                    required
                    autocomplete="name"
                    placeholder="Full name"
                />
                <InputError class="mt-2" message={errors.name} />
            </div>

            <div class="grid gap-2">
                <Label for="handle">Public handle</Label>
                <Input
                    id="handle"
                    name="handle"
                    class="mt-1 block w-full"
                    value={user.handle ?? ''}
                    autocomplete="off"
                    placeholder="yourname"
                    data-test="profile-handle"
                />
                <p class="text-sm text-muted-foreground">
                    This is how people can find you in Contacts.
                </p>
                <InputError class="mt-2" message={errors.handle} />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    class="mt-1 block w-full"
                    value={user.email}
                    required
                    autocomplete="username"
                    placeholder="Email address"
                    disabled
                />
                <InputError class="mt-2" message={errors.email} />
            </div>

            <div class="grid gap-2">
                <Label for="social_x_handle">X handle</Label>
                <Input
                    id="social_x_handle"
                    name="social_x_handle"
                    class="mt-1 block w-full"
                    value={user.social_x_handle ?? ''}
                    autocomplete="off"
                    placeholder="yourhandle"
                    data-test="profile-x-handle"
                />
                <InputError class="mt-2" message={errors.social_x_handle} />
            </div>

            <div class="grid gap-2">
                <Label for="social_github_handle">GitHub handle</Label>
                <Input
                    id="social_github_handle"
                    name="social_github_handle"
                    class="mt-1 block w-full"
                    value={user.social_github_handle ?? ''}
                    autocomplete="off"
                    placeholder="yourhandle"
                    data-test="profile-github-handle"
                />
                <InputError
                    class="mt-2"
                    message={errors.social_github_handle}
                />
            </div>

            <div class="flex items-center gap-4">
                <Button
                    type="submit"
                    disabled={processing}
                    data-test="update-profile-button">Save</Button
                >
            </div>
        {/snippet}
    </Form>
</div>

<div class="mt-12 flex flex-col space-y-6">
    <Heading
        variant="small"
        title="Branding"
        description="Your palette of six brand colors. They appear as one-click shortcuts next to every color picker in the editor."
    />

    <div class="grid gap-4 sm:grid-cols-2">
        {#each BRANDING_KEYS as key (key)}
            <div class="grid gap-2">
                <Label for="branding-{key}">{BRANDING_LABELS[key]}</Label>
                <div class="flex items-center gap-3">
                    <ColorField
                        id="branding-{key}"
                        class="h-9 w-16 cursor-pointer rounded-md border"
                        value={brandingForm.branding[key]}
                        onchange={(color) =>
                            (brandingForm.branding[key] = color)}
                        showSwatches={false}
                        dataTest="branding-{key}"
                    />
                    <span class="font-mono text-xs text-muted-foreground">
                        {brandingForm.branding[key]}
                    </span>
                </div>
                <InputError
                    class="mt-1"
                    message={brandingForm.errors[`branding.${key}`]}
                />
            </div>
        {/each}
    </div>

    <div class="flex items-center gap-4">
        <Button
            type="button"
            onclick={saveBranding}
            disabled={brandingForm.processing}
            data-test="save-branding-button">Save branding</Button
        >
        <Button type="button" variant="ghost" onclick={resetBranding}>
            Reset to defaults
        </Button>
    </div>
</div>

<DeleteUser />
