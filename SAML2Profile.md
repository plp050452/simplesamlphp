# SAML 2.0 Profiles supported #

simpleSAMLphp supports:
  * AuthNRequest using HTTP-REDIRECT (with or without signatures)
  * Receiving HTTP-POST AuthNResponse with XMLsec signatures
  * Supports both SP and IdP initiated HTTP-REDIRECT SLO (LogoutRequest/LogoutResponse)

  * No support for Artifact profile.
  * No support for SOAP logout
  * No support for PAOS profile.

# PKI #

Currently there is an issue with the openssl library in PHP resulting in problems with using DSA certificates. For now use RSA.
