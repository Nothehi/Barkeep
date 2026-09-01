#!/bin/sh
# Fetches the swoole extension source and prepares it for `pecl install`.
#
# The source comes from GitHub rather than from PECL because pecl.php.net is not
# routable from every build host. The GitHub archive is otherwise a faithful
# copy, except that swoole's .gitattributes marks core-tests/, benchmark/ and
# .github/ as export-ignore while package.xml still lists every file in them.
# pecl refuses to install a package whose file list does not resolve, so the
# entries with nothing behind them are dropped from the manifest here. None of
# them are inputs to config.m4, so the extension itself is unaffected.
#
# Usage: swoole-source.sh <version> <destination>

set -eu

version="$1"
destination="$2"

mkdir -p "$destination"

curl -fsSL "https://github.com/swoole/swoole-src/archive/refs/tags/v${version}.tar.gz" \
    | tar -xz -C "$destination" --strip-components=1

php -r '
$root = $argv[1];
$manifest = $root . "/package.xml";
$document = new DOMDocument();
$document->load($manifest);
$xpath = new DOMXPath($document);
$xpath->registerNamespace("p", $document->documentElement->namespaceURI);
$dropped = 0;
foreach ($xpath->query("//p:contents//p:file") as $file) {
    $path = $root . "/" . ltrim($file->getAttribute("name"), "/");
    if (!file_exists($path)) {
        $file->parentNode->removeChild($file);
        $dropped++;
    }
}
$document->save($manifest);
printf("Dropped %d unexported file(s) from package.xml.%s", $dropped, PHP_EOL);
' "$destination"
