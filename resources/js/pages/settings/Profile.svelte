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
    import { Form, page } from '@inertiajs/svelte';
    import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
    import AppHead from '@/components/AppHead.svelte';
    import DeleteUser from '@/components/DeleteUser.svelte';
    import Heading from '@/components/Heading.svelte';
    import { Button, Input } from 'daisy-svelte';

    const user = $derived(page.props.auth.user);
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
            <Input
                label="Name"
                id="name"
                name="name"
                value={user.name}
                required
                autocomplete="name"
                placeholder="Full name"
                error={errors.name}
            />

            <Input
                label="Public handle"
                id="handle"
                name="handle"
                value={user.handle ?? ''}
                autocomplete="off"
                placeholder="yourname"
                hint="This is how people can find you in Contacts."
                error={errors.handle}
                data-test="profile-handle"
            />

            <Input
                label="Email address"
                id="email"
                type="email"
                name="email"
                value={user.email}
                required
                autocomplete="username"
                placeholder="Email address"
                disabled
                error={errors.email}
            />

            <Input
                label="X handle"
                id="social_x_handle"
                name="social_x_handle"
                value={user.social_x_handle ?? ''}
                autocomplete="off"
                placeholder="yourhandle"
                error={errors.social_x_handle}
                data-test="profile-x-handle"
            />

            <Input
                label="GitHub handle"
                id="social_github_handle"
                name="social_github_handle"
                value={user.social_github_handle ?? ''}
                autocomplete="off"
                placeholder="yourhandle"
                error={errors.social_github_handle}
                data-test="profile-github-handle"
            />

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

<DeleteUser />
