# MediaWiki DeepZoom
## Install

Add the following to your `LocalSettings.php`.

```php
wfLoadExtension('DeepZoom');
$wgFileExtensions[] = 'dzi';
unset($wgMimeTypeExclusions[array_search('application/xml', $wgMimeTypeExclusions)]);
```

## Usage

At the moment this extension is not compatible with MultimediaViewer. To link to the file page of a DZI use markup similar to the following:

```
[[File:Utrecht.dzi|frame|left|[[:File:Utrecht.dzi|Map of Utrecht]]]]
```

The default orientation of the image can be provided like so

```
[[File:Utrecht.dzi|frame|left|z=11.311342592592595|x=0.5383857802939661|y=0.348383948588566|[[:File:Utrecht.dzi|Dithering on a map of Utrecht]]]]

```

## Debugging
```
php maintenance/rebuildLocalisationCache.php
```