# Tar archive stream-based reader *[WIP]*

> Tato knihovna je zatím jen zkušební prototyp se záměrem otestovat výkon přímého sekvenčního čtení z TAR archivu.  
> Zkoušel jsem ostatní dostupné knihovny, Phar i další PECL balíčky podporují náhodný přístup – tedy projdou
> nejdříve celý archiv, což shodí PHP na paměti. Tato knihovna podporuje pouze sekvenční čtení streamu přes iterátor.

> `WIP` **Work in progress**