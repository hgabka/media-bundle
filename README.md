# hgabka/media-bundle

Médiakezelő (média-könyvtár) bundle Symfonyhoz és Sonata Adminhoz: mappastruktúrába rendezett fájlok, képek, PDF-ek és távoli (YouTube/Vimeo/Dailymotion videó, SoundCloud hang, slideshow) elemek feltöltése, kezelése, kiválasztása űrlapokból és CKEditorból, Imagine alapú képátméretezéssel. A Kunstmaan MediaBundle ötleteire épül.

## Telepítés

```bash
composer require hgabka/media-bundle
```

```php
// config/bundles.php
Hgabka\MediaBundle\HgabkaMediaBundle::class => ['all' => true],
```

Függőségek: `hgabka/utils-bundle`, `liip/imagine-bundle`, `knplabs/gaufrette`, `twig/intl-extra`. Az entitások táblái (`hg_media_media`, `hg_media_folders`, + fordítás táblák) Doctrine migrációval hozhatók létre. A kezdő mappákat a `DataFixtures\ORM\FolderFixtures` tölti be.

Routing (`config/routes.yaml`):

```yaml
hgabka_media:
    resource: '@HgabkaMediaBundle/Resources/config/routing.yaml'

hgabka_media_public:
    resource: '@HgabkaMediaBundle/Resources/config/routing_public.yaml'
```

Az útvonalak: `/media/…` (média-műveletek, chooser), `/media/folder/…`, `/media/icon-font/…`, `/media/public/…` (nyilvános letöltés/megjelenítés).

Frontend: az `assets/` (JS, CSS) és a `package.json` a médiakezelő UI-hoz tartozik; a Webpack Encore konfigot (`webpack.config.js`) a projektbe kell illeszteni.

## Konfiguráció

```yaml
# config/packages/hgabka_media.yaml
hgabka_media:
    liip_imagine_cache_prefix: uploads/cache
    liip_imagine_web_root_dir: '%kernel.project_dir%/public'
    soundcloud_api_key: YOUR_CLIENT_ID
    remote_video:
        vimeo: true
        youtube: true
        dailymotion: true
    enable_pdf_preview: false          # true esetén a pdf_preview.yaml is betöltődik (PdfTransformer)
    blacklisted_extensions: [php, htaccess]
    folder_depth: 4                    # a feltöltött fájlok almappa-mélysége a fájlrendszerben
    protected_media_download_role: ROLE_MEDIA_ADMIN
    default_ckeditor_folders:
        images: imageroot              # mappa `internal_name`-je
        files: fileroot
```

Paraméterek (felülírhatók): `hgabka_media.upload_dir` / `hgabka_media.media_path` (alapból `/uploads/media/`, a `public/` alatt), `hgabka_media.protected_media_path` (`data/media/`, a projekt gyökerében, webről nem elérhető), `aviary_api_key`.

A bundle `prepend`-del a Twig (globálisok: `upload_dir`, `mediabundleisactive`, `mediamanager`) és a LiipImagine konfigurációba is beír (`imagine_filters.yaml`: `media_list_thumbnail`, `media_detail_thumbnail`, stb.), valamint saját `CacheManager`, `WebPathResolver`, `ImagineController` osztályokra cseréli a LiipImagine megfelelőit, és `BackgroundFilterLoader`-t regisztrál.

## Működés

### Entitások

- `Folder` – hierarchikus (nested set: `lft`/`lvl`/`rgt`), fordítható mappa (`FolderTranslation`), `internal_name` alapján is kereshető, soft delete.
- `Media` – egy médiaelem: `name`, `location`, `content_type`, `metadata` (JSON), `filesize`, `url`, `original_filename`, `deleted`, `removed_from_file_system`, `protected` (védett fájlok a webgyökéren kívül tárolódnak); fordítható (`MediaTranslation`).

### Handlerek

A `Helper\MediaManager` a `hgabka_media.media_handler` taggel ellátott handlerek (`AbstractMediaHandler`) közül választ típus szerint (prioritás alapján):

| Handler | Típus |
|---|---|
| `ImageHandler` | képek (átméretezés Imagine-nel, védett mód is) |
| `FileHandler` | általános fájlok |
| `PdfHandler` | PDF (előnézettel, ha `enable_pdf_preview`) |
| `RemoteVideoHandler` | YouTube / Vimeo / Dailymotion |
| `RemoteAudioHandler` | SoundCloud |
| `RemoteSlideHandler` | slideshow (pl. Slideshare) |

Saját típus: származtass az `AbstractMediaHandler`-ből (`getName`, `getType`, `getFormType`, `canHandle`, `prepareMedia`, `saveMedia`, `removeMedia`, `createNew`, `getAddFolderActions`, …), és taggeld `hgabka_media.media_handler`-rel (a `MediaHandlerCompilerPass` gyűjti össze). A fájlok a Gaufrette `hgabka_media.filesystem` / `hgabka_media.filesystem_protected` fájlrendszereken (alapból helyi adapter) tárolódnak.

A `MediaManager` hasznos metódusai: `saveMedia()`, `removeMedia()`, `createNew()`, `getMediaPath()`, `getMediaContent()`, `getMediaSize()`, `convertToProtected()` / `convertToNonProtected()`, `getDownloadResponse()`.

### Űrlaptípusok

- `Form\Type\MediaType`, `MediaSimpleType`, `MediaMultiType` – média kiválasztása entitás-mezőhöz (chooser popup / egyszerű feltöltés / több elem)
- `Form\Type\IconFontType` – ikon választó (`Helper\IconFont\*` loaderekkel)
- `Form\FolderType`, `SubFolderType`, `BulkUploadType`, `BulkMoveMediaType`, `Form\Remote*\*Type`, `Form\File\FileType`
- Validátorok: `Validator\Constraints\Media`, `HasGuessableExtension`

### Twig

`get_media_for_folder`, `get_last_media_for_folder`, `get_folder_by_internal_name`, `is_folder_traversable`, `render_svg_media`, `is_svg`.

### Admin

`Admin\MediaAdmin` és a `MediaMenuAdaptor` / `AdminMenuListener` a Sonata (utils-bundle-féle) menübe illeszti a médiakönyvtárat. Védett médiák letöltéséhez a `protected_media_download_role` szerep szükséges.

### Parancsok

| Parancs | Leírás |
|---|---|
| `hgabka:media:rebuild-folder-tree` | a mappafa (`lft`/`rgt`) újraépítése |
| `hgabka:media:rename-soft-deleted` | a soft-deleted médiák fájljainak átnevezése |
| `hgabka:media:clean-deleted-media` | az adatbázisban törölt médiák fizikai fájljainak törlése |
