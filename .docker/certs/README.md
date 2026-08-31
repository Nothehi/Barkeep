# Extra CA certificates

Drop `*.crt` files (PEM encoded) here and they are added to the trust store of
every image built from this repo, both at build time and at runtime.

This directory exists for networks that terminate and re-sign outbound TLS. On
such a network `composer install` and `npm ci` fail inside the build with
`unable to verify the first certificate` / `SSL certificate problem`, because
the container trusts the public CAs but not the inspecting proxy's root.

Get the proxy's **root** certificate from whoever runs the proxy and save it
here as a `.crt` file. Inspecting proxies usually present only the re-signed
leaf, so you cannot always scrape the root off the wire — this shows you the
issuer name to ask for:

    openssl s_client -connect registry.npmjs.org:443 \
        -servername registry.npmjs.org </dev/null 2>/dev/null \
      | openssl x509 -noout -issuer

If the proxy does send its chain, the root is the last certificate in:

    openssl s_client -showcerts -connect registry.npmjs.org:443 \
        -servername registry.npmjs.org </dev/null 2>/dev/null

The directory is otherwise empty and the `COPY` is a no-op, so nothing changes
on a network that does not inspect TLS.
