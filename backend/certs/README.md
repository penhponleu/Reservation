Put Aiven's **ca.pem** (MySQL service → Overview → CA certificate → Download) in this folder.

It is a public certificate, not a password, so it is safe to commit.
Railway reads it via `MYSQL_ATTR_SSL_CA=certs/ca.pem`.
