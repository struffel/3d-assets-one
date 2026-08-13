import { type AssetType } from "./asset-types.ts";

export default class Asset {
  constructor(
    public id: number,
    public title: string,
    protected type: AssetType,
    private readonly creatorId: number,
  ) {}
}

export class StoredAsset extends Asset {
  constructor(id: number, title: string, type: AssetType, creatorId: number, public clicks: number) {
    super(id, title, type, creatorId);
  }

  getThumbnailUrl(format: string): string {
    return `/thumbnail/${format}/${this.id}.jpg`;
  }
}