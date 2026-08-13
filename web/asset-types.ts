export const ASSET_TYPES = ["other", "pbr-material", "model-3d", "hdri"] as const;
export type AssetType = typeof ASSET_TYPES[number];

export function isAssetType(value: string): value is AssetType {
    return (ASSET_TYPES as readonly string[]).includes(value);
}