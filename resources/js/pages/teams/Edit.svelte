<script module lang="ts">
    import { edit, index } from '@/routes/teams';
    import type { Team } from '@/types';

    export const layout = (props: { team: Team }) => ({
        breadcrumbs: [
            {
                title: 'Teams',
                href: index(),
            },
            {
                title: props.team.name,
                href: edit(props.team.slug),
            },
        ],
    });
</script>

<script lang="ts">
    import { Form, router } from '@inertiajs/svelte';
    import ChevronDown from 'lucide-svelte/icons/chevron-down';
    import Mail from 'lucide-svelte/icons/mail';
    import UserPlus from 'lucide-svelte/icons/user-plus';
    import X from 'lucide-svelte/icons/x';
    import AppHead from '@/components/AppHead.svelte';
    import CancelInvitationModal from '@/components/CancelInvitationModal.svelte';
    import DeleteTeamModal from '@/components/DeleteTeamModal.svelte';
    import Heading from '@/components/Heading.svelte';
    import InviteMemberModal from '@/components/InviteMemberModal.svelte';
    import RemoveMemberModal from '@/components/RemoveMemberModal.svelte';
    import UserAvatar from '@/components/UserAvatar.svelte';
    import { Badge, Button, Dropdown, Input, Tooltip } from 'daisy-svelte';
    import { update } from '@/routes/teams';
    import { update as updateMember } from '@/routes/teams/members';
    import type {
        RoleOption,
        TeamInvitation,
        TeamMember,
        TeamPermissions,
    } from '@/types';

    let {
        team,
        members,
        invitations,
        permissions,
        availableRoles,
    }: {
        team: Team;
        members: TeamMember[];
        invitations: TeamInvitation[];
        permissions: TeamPermissions;
        availableRoles: RoleOption[];
    } = $props();

    let inviteDialogOpen = $state(false);
    let deleteDialogOpen = $state(false);
    let removeMemberDialogOpen = $state(false);
    let memberToRemove = $state<TeamMember | null>(null);
    let cancelInvitationDialogOpen = $state(false);
    let invitationToCancel = $state<TeamInvitation | null>(null);

    const pageTitle = $derived(
        permissions.canUpdateTeam ? `Edit ${team.name}` : `View ${team.name}`,
    );

    const updateMemberRole = (member: TeamMember, newRole: string) => {
        router.visit(updateMember([team.slug, member.id]), {
            data: { role: newRole },
            preserveScroll: true,
        });
    };

    const confirmRemoveMember = (member: TeamMember) => {
        memberToRemove = member;
        removeMemberDialogOpen = true;
    };

    const confirmCancelInvitation = (invitation: TeamInvitation) => {
        invitationToCancel = invitation;
        cancelInvitationDialogOpen = true;
    };
</script>

<AppHead title={pageTitle} />

<h1 class="sr-only">{pageTitle}</h1>

<div class="flex flex-col space-y-10">
    <div class="space-y-6">
        {#if permissions.canUpdateTeam}
            <Heading
                variant="small"
                title="Team settings"
                description="Update your team name and settings"
            />

            <Form {...update.form(team.slug)} class="space-y-6">
                {#snippet children({ errors, processing })}
                    <Input
                        label="Team name"
                        id="name"
                        name="name"
                        value={team.name}
                        required
                        error={errors.name}
                        data-test="team-name-input"
                    />

                    <div class="flex items-center gap-4">
                        <Button
                            type="submit"
                            disabled={processing}
                            data-test="team-save-button">Save</Button
                        >
                    </div>
                {/snippet}
            </Form>
        {:else}
            <Heading variant="small" title={team.name} />
        {/if}
    </div>

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Team members"
                description={permissions.canCreateInvitation
                    ? 'Manage who belongs to this team'
                    : ''}
            />

            {#if permissions.canCreateInvitation}
                <Button
                    onclick={() => (inviteDialogOpen = true)}
                    data-test="invite-member-button"
                >
                    <UserPlus class="h-4 w-4" /> Invite member
                </Button>
            {/if}
        </div>

        <div class="space-y-3">
            {#each members as member (member.id)}
                <div
                    class="flex items-center justify-between rounded-lg border p-4"
                    data-test="member-row"
                >
                    <div class="flex items-center gap-4">
                        <UserAvatar
                            name={member.name}
                            avatar={member.avatar}
                            class="h-10 w-10"
                        />

                        <div>
                            <div class="font-medium">{member.name}</div>
                            <div class="text-sm text-muted-foreground">
                                {member.email}
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        {#if member.role !== 'owner' && permissions.canUpdateMember}
                            <Dropdown align="end">
                                {#snippet trigger()}
                                    <span
                                        class="btn btn-outline btn-sm"
                                        data-test="member-role-trigger"
                                    >
                                        {member.role_label}
                                        <ChevronDown
                                            class="ml-2 h-4 w-4 opacity-50"
                                        />
                                    </span>
                                {/snippet}
                                {#snippet children({ close })}
                                    {#each availableRoles as role (role.value)}
                                        <li>
                                            <button
                                                type="button"
                                                data-test="member-role-option"
                                                onclick={() => {
                                                    close();
                                                    updateMemberRole(
                                                        member,
                                                        role.value,
                                                    );
                                                }}
                                            >
                                                {role.label}
                                            </button>
                                        </li>
                                    {/each}
                                {/snippet}
                            </Dropdown>
                        {:else}
                            <Badge variant="secondary"
                                >{member.role_label}</Badge
                            >
                        {/if}

                        {#if member.role !== 'owner' && permissions.canRemoveMember}
                            <Tooltip tip="Remove member">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    data-test="member-remove-button"
                                    onclick={() => confirmRemoveMember(member)}
                                >
                                    <X class="h-4 w-4" />
                                </Button>
                            </Tooltip>
                        {/if}
                    </div>
                </div>
            {/each}
        </div>
    </div>

    {#if invitations.length > 0}
        <div class="space-y-6">
            <Heading
                variant="small"
                title="Pending invitations"
                description="Invitations that haven't been accepted yet"
            />

            <div class="space-y-3">
                {#each invitations as invitation (invitation.code)}
                    <div
                        class="flex items-center justify-between rounded-lg border p-4"
                        data-test="invitation-row"
                    >
                        <div class="flex items-center gap-4">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-muted"
                            >
                                <Mail class="h-5 w-5 text-muted-foreground" />
                            </div>
                            <div>
                                <div class="font-medium">
                                    {invitation.email}
                                </div>
                                <div class="text-sm text-muted-foreground">
                                    {invitation.role_label}
                                </div>
                            </div>
                        </div>

                        {#if permissions.canCancelInvitation}
                            <Tooltip tip="Cancel invitation">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    data-test="invitation-cancel-button"
                                    onclick={() =>
                                        confirmCancelInvitation(invitation)}
                                >
                                    <X class="h-4 w-4" />
                                </Button>
                            </Tooltip>
                        {/if}
                    </div>
                {/each}
            </div>
        </div>
    {/if}

    {#if permissions.canDeleteTeam && !team.isPersonal}
        <div class="space-y-6">
            <Heading
                variant="small"
                title="Delete team"
                description="Permanently delete your team"
            />
            <div
                class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10"
            >
                <div
                    class="relative space-y-0.5 text-red-600 dark:text-red-100"
                >
                    <p class="font-medium">Warning</p>
                    <p class="text-sm">
                        Please proceed with caution, this cannot be undone.
                    </p>
                </div>

                <Button
                    variant="destructive"
                    onclick={() => (deleteDialogOpen = true)}
                    data-test="delete-team-button"
                >
                    Delete team
                </Button>
            </div>
        </div>
    {/if}
</div>

{#if permissions.canCreateInvitation}
    <InviteMemberModal {team} {availableRoles} bind:open={inviteDialogOpen} />
{/if}

<RemoveMemberModal
    {team}
    member={memberToRemove}
    bind:open={removeMemberDialogOpen}
/>

<CancelInvitationModal
    {team}
    invitation={invitationToCancel}
    bind:open={cancelInvitationDialogOpen}
/>

{#if permissions.canDeleteTeam && !team.isPersonal}
    <DeleteTeamModal {team} bind:open={deleteDialogOpen} />
{/if}
