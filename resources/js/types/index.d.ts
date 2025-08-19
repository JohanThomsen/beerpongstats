import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    // Relationships
    teams?: Team[];
    games?: Game[];
    pivot?: GameUser;
}

export type BreadcrumbItemType = BreadcrumbItem;

// Enums
export enum GameResult {
    WIN = 'WIN',
    LOSS = 'LOSS',
}

export enum GameType {
    SIX_CUP = 'SIX_CUP',
    TEN_CUP = 'TEN_CUP',
}

export enum GameUpdateType {
    START = 'START',
    END = 'END',
    MISS = 'MISS',
    EDGE = 'EDGE',
    HIT = 'HIT',
    RERACK = 'RERACK',
}

// Base Models
export interface Team {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
    // Relationships
    users?: User[];
    games?: Game[];
    pivot?: GameTeam;
}

export interface Game {
    id: number;
    type: GameType;
    is_ended: boolean;
    is_solo: boolean;
    created_at: string;
    updated_at: string;
    // Relationships
    teams?: Team[];
    users?: User[];
    game_updates?: GameUpdate[];
}

export interface GameUpdate {
    id: number;
    game_id: number;
    type: GameUpdateType;
    cup_positions: number[] | any[]; // JSON array
    created_at: string;
    updated_at: string;
    // Relationships
    game?: Game;
}

// Pivot Models
export interface GameTeam {
    id: number;
    game_id: number;
    team_id: number;
    result: GameResult | null;
    cups_left: number | null;
}

export interface GameUser {
    id: number;
    game_id: number;
    user_id: number;
    result: GameResult | null;
    cups_left: number | null;
}

export interface TeamUser {
    id: number;
    team_id: number;
    user_id: number;
}
