declare namespace App.DataObjects {
export type ProfileGameDataObject = {
id: number;
isSolo: boolean;
type: App.Enums.GameType;
isEnded: boolean;
totalThrows: string;
hitRate: string;
edgeHitRate: string;
missRate: string;
primaryUser: App.DataObjects.UserGameDataObject | null;
secondaryUser: App.DataObjects.UserGameDataObject | null;
primaryTeam: App.DataObjects.TeamGameDataObject | null;
secondaryTeam: App.DataObjects.TeamGameDataObject | null;
updatedAt: string;
createdAt: string;
};
export type TeamGameDataObject = {
id: number;
name: string;
users: Array<any>;
result: App.Enums.GameResult | null;
cupsLeft: number | null;
};
export type UserGameDataObject = {
id: number;
name: string;
result: App.Enums.GameResult | null;
cupsLeft: number | null;
};
}
declare namespace App.Enums {
export type GameResult = 'WIN' | 'LOSS';
export type GameType = 'SIX_CUP' | 'TEN_CUP';
export type GameUpdateType = 'START' | 'END' | 'MISS' | 'EDGE' | 'HIT' | 'RERACK';
}
