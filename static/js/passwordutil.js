(function(scope) {
	'use strict';

	function generate_key(passphrase) {
		openpgp.config.show_version = false;
		openpgp.config.show_comment = false;
		return openpgp.generateKey({
			passphrase: passphrase,
			curve: 'ed25519',
			userIds: [{
				name: 'a',
				email: 'a@a.aa'
			}]
		}).then(function(value) {
			return new Promise(function(resolve) {
				resolve([
					value.publicKeyArmored.replace(/^-----[^-]+-----$/mg, '').replace(/[\t\r\n ]/g, ''),
					value.privateKeyArmored.replace(/^-----[^-]+-----$/mg, '').replace(/[\t\r\n ]/g, '')
				]);
			});
		});
	}

	function read_public_key(text) {
		text = '-----BEGIN PGP PUBLIC KEY BLOCK-----\r\n\r\n' + text + '\r\n-----END PGP PUBLIC KEY BLOCK-----';
		return openpgp.key.readArmored(text).then(function(value) {
			return new Promise(function(resolve, reject) {
				if (value.err) {
					reject(value.err[0]);
					return;
				}
				resolve(value.keys[0]);
			});
		});
	}

	function read_private_key(text) {
		text = '-----BEGIN PGP PRIVATE KEY BLOCK-----\r\n\r\n' + text + '\r\n-----END PGP PRIVATE KEY BLOCK-----';
		return openpgp.key.readArmored(text).then(function(value) {
			return new Promise(function(resolve, reject) {
				if (value.err) {
					reject(value.err[0]);
					return;
				}
				resolve(value.keys[0]);
			});
		});
	}

	function encrypt_private_key(text, passphrase) {
		openpgp.config.show_version = false;
		openpgp.config.show_comment = false;
		var key;
		return read_private_key(text).then(function(value) {
			key = value;
			return key.encrypt(passphrase);
		}).then(function(value) {
			return new Promise(function(resolve) {
				resolve(
					key.armor().replace(/^-----[^-]+-----$/mg, '').replace(/[\t\r\n ]/g, '')
				);
			});
		});
	}

	function decrypt_private_key(text, passphrase) {
		openpgp.config.show_version = false;
		openpgp.config.show_comment = false;
		var key;
		return read_private_key(text).then(function(value) {
			key = value;
			return key.decrypt(passphrase);
		}).then(function(value) {
			return new Promise(function(resolve) {
				resolve(
					key.armor().replace(/^-----[^-]+-----$/mg, '').replace(/[\t\r\n ]/g, '')
				);
			});
		});
	}

	function do_encrypt(text, public_key) {
		openpgp.config.show_version = false;
		openpgp.config.show_comment = false;
		return openpgp.encrypt({
			message: openpgp.message.fromText(text),
			publicKeys: [public_key]
		}).then(function(value) {
			return new Promise(function(resolve) {
				resolve(
					value.data.replace(/^-----[^-]+-----$/mg, '').replace(/[\t\r\n ]/g, '')
				);
			});
		});
	}

	function do_decrypt(text, private_key) {
		text = '-----BEGIN PGP MESSAGE-----\r\n\r\n' + text + '\r\n-----END PGP MESSAGE-----';
		return openpgp.message.readArmored(text).then(function(value) {
			return openpgp.decrypt({
				message: value,
				privateKeys: [private_key]
			});
		}).then(function(value) {
			return new Promise(function(resolve) {
				resolve(
					value.data
				);
			});
		});
	}

	function encrypt(text, public_key) {
		if (typeof public_key === 'string') {
			return read_public_key(public_key).then(function(value) {
				return do_encrypt(text, value);
			});
		}
		return do_encrypt(text, public_key);
	}

	function decrypt(text, private_key) {
		if (typeof private_key === 'string') {
			return read_private_key(private_key).then(function(value) {
				return do_decrypt(text, value);
			});
		}
		return do_decrypt(text, private_key);
	}


	function password_hash_v1(str, salt) {
		return openpgp.crypto.hash.md5(openpgp.util.encode_utf8(str)).then(function(value) {
			str = openpgp.util.Uint8Array_to_hex(value) + salt;
			return openpgp.crypto.hash.md5(openpgp.util.encode_utf8(str));
		}).then(function(value) {
			return new Promise(function(resolve) {
				resolve(openpgp.util.Uint8Array_to_hex(value));
			});
		});
	}

	function password_hash_v2(str, salt) {
		return dcodeIO.bcrypt.hash(str, salt);
	}

	function password_hash_v3(str, salt) {
		return openpgp.crypto.hash.sha512(openpgp.util.encode_utf8(str)).then(function(value) {
			str = openpgp.util.Uint8Array_to_str(value);
			return openpgp.crypto.hash.md5(openpgp.util.encode_utf8(salt));
		}).then(function(value) {
			salt = '$2y$11$' + dcodeIO.bcrypt.encodeBase64(value, value.length);
			return dcodeIO.bcrypt.hash(str, salt);
		});
	}

	function password_hash(str, salt, password_version) {
		var fn;
		switch (parseInt(password_version)) {
			case 1:
				fn = password_hash_v1;
				break;
			case 2:
				fn = password_hash_v2;
				break;
			default:
				fn = password_hash_v3;
				break;
		}
		return fn(str, salt);
	}

	function random_string(len) {
		return openpgp.crypto.random.getRandomBytes(len).then(function(value) {
			return new Promise(function(resolve) {
				resolve(openpgp.util.Uint8Array_to_b64(value));
			});
		});
	}

	function hash(algo, str) {
		var fn = openpgp.crypto.hash[algo];
		return fn(openpgp.util.encode_utf8(str)).then(function(value) {
			return new Promise(function(resolve) {
				resolve(openpgp.util.Uint8Array_to_hex(value));
			});
		});
	}

	function base64_encode(str) {
		return openpgp.util.Uint8Array_to_b64(openpgp.util.encode_utf8(str));
	}

	function base64_decode(str) {
		return openpgp.util.decode_utf8(openpgp.util.b64_to_Uint8Array(str));
	}

	scope.PasswordUtil = {
		generate_key: generate_key,
		read_public_key: read_public_key,
		read_private_key: read_private_key,
		encrypt_private_key: encrypt_private_key,
		decrypt_private_key: decrypt_private_key,
		encrypt: encrypt,
		decrypt: decrypt,
		password_hash: password_hash,
		random_string: random_string,
		hash: hash,
		base64_encode: base64_encode,
		base64_decode: base64_decode,
	};

})(this);
