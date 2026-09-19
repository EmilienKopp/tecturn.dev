<?php

namespace App\Http\Navigation;

use App\Models\User;
use Spatie\Navigation\Navigation;
use Spatie\Navigation\Section;

class AppNavigation
{
    /**
     * Build the sidebar navigation tree for the given user.
     *
     * @return array<int, array{url: string, title: string, active: bool, attributes: array<string, string>, children: array<int, mixed>, depth: int}>
     */
    public function tree(?User $user): array
    {
        $team = $user?->currentTeam;

        return Navigation::make()
            ->addIf(condition: $team !== null, title: 'Platform', configure: function (Section $section) use ($team) {
                $section
                    ->add('Dashboard', route('dashboard', $team->slug), attributes: ['icon' => 'layout-grid'])
                    ->add('Contacts', route('contacts.index'), attributes: ['icon' => 'book-user'])
                    ->add('Presentations', route('presentations.index', $team->slug), attributes: ['icon' => 'presentation'])
                    ->add('Rehearsals', route('rehearsals.index', $team->slug), attributes: ['icon' => 'timer'])
                    ->add('Reviews', route('reviews.index'), attributes: ['icon' => 'message-square']);
            })
            ->addIf(condition: $user !== null, title: 'Settings', configure: function (Section $section) {
                $section
                    ->add('Profile', route('profile.edit'), attributes: ['icon' => 'user'])
                    ->add('Branding', route('branding.edit'), attributes: ['icon' => 'palette'])
                    ->add('Teams', route('teams.index'), attributes: ['icon' => 'users']);
            })
            ->add(title: 'Help', configure: function (Section $section) {
                $section->add('Documentation', route('docs'), attributes: ['icon' => 'book-open']);
            })
            ->tree();
    }
}
