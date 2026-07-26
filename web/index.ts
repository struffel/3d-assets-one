// A "type" describing a plain object shape — like a PHP associative array with fixed keys
type Creator = {
  id: number;
  slug: string;
  //title: string;
  licenseUrl: string | null; // union type: string OR null (like PHP's ?string)
};

const ambientCg: Creator = {
  id: 1,
  slug: "ambientcg",
  title: "ambientCG",
  licenseUrl: "https://ambientcg.com/list?type=license",
};

console.log(ambientCg.title);