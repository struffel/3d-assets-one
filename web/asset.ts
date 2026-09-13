import { type AssetType } from "./asset-types.ts";

export type Asset = {
  id: number;
  title: string;
  type: AssetType;
  creatorId: number;
};

export type StoredAsset = Asset & {
  clicks: number;
};

export function getThumbnailUrl(asset: StoredAsset, format: string): string {
    return `/thumbnail/${format}/${asset.id}.jpg`;
  }