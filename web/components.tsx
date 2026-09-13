import type { Asset, StoredAsset } from "./asset.ts";


type HeaderProps = {
    siteName: string;
};

export function Header(props: HeaderProps) {
    return (
        <header>
            <a href="/" class="logo">{props.siteName}</a>
            <nav>
                <a href="/about-site">About</a>
                <a href="/about-creators">Creators</a>
            </nav>
        </header>
    );
}

export function AssetList({ assets }: { assets: StoredAsset[] }) {
    return (
        <ul>
            {assets.map((a) => (
                <li key={a.id}>{a.title}</li>
            ))}
        </ul>
    );
}


export function SearchForm() {
    return (
        <form
            id="asset-filters-form"
            hx-get="/search"
            hx-target="main"
            hx-trigger="change,load,input delay:100ms"
        >
            <input type="text" name="q" />
        </form>
    );
}