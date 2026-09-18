import BookOpen from 'lucide-svelte/icons/book-open';
import BookUser from 'lucide-svelte/icons/book-user';
import LayoutGrid from 'lucide-svelte/icons/layout-grid';
import MessageSquare from 'lucide-svelte/icons/message-square';
import Presentation from 'lucide-svelte/icons/presentation';
import Timer from 'lucide-svelte/icons/timer';
import User from 'lucide-svelte/icons/user';
import Users from 'lucide-svelte/icons/users';
import type { NavItem, ServerNavItem } from '@/types';

const icons: Record<string, NavItem['icon']> = {
    'book-open': BookOpen,
    'book-user': BookUser,
    'layout-grid': LayoutGrid,
    'message-square': MessageSquare,
    presentation: Presentation,
    timer: Timer,
    user: User,
    users: Users,
};

export function toNavItem(item: ServerNavItem): NavItem {
    return {
        title: item.title,
        href: item.url,
        icon: item.attributes.icon ? icons[item.attributes.icon] : undefined,
        isActive: item.active,
    };
}
