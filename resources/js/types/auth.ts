/**
 * The user's branding: six named colors plus default typography. The editor's
 * single source of defaults — `background` (hex or linear-gradient) is the
 * default slide background, `primary` the default text color, and the
 * typography slots seed new text blocks. Mirrors App\Support\Branding.
 */
export type BrandingColors = {
    background: string;
    primary: string;
    secondary: string;
    accent: string;
    success: string;
    danger: string;
    fontFamily: string | null;
    fontSize: string | null;
    fontWeight: string | null;
};

export type User = {
    id: number;
    name: string;
    handle?: string | null;
    email: string;
    avatar?: string;
    social_x_handle?: string | null;
    social_github_handle?: string | null;
    branding: BrandingColors;
    /* @chisel-2fa */
    two_factor_enabled?: boolean;
    /* @end-chisel-2fa */
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

/* @chisel-passkeys */
export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};
/* @end-chisel-passkeys */

/* @chisel-2fa */
export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
/* @end-chisel-2fa */
