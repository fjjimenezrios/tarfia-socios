#!/usr/bin/env python3
"""
generate_env.py — Genera el archivo .env raíz con secretos aleatorios.
Uso: python3 utils/generate_env.py [--force]
"""
import argparse
import os
import secrets
import string
import sys


ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
ENV_PATH = os.path.join(ROOT, ".env")
EXAMPLE_PATH = os.path.join(ROOT, ".env.example")


def random_secret_key(length: int = 50) -> str:
    """Genera una SECRET_KEY de Django con el alfabeto recomendado."""
    alphabet = string.ascii_letters + string.digits + "!@#$%^&*(-_=+)"
    return "".join(secrets.choice(alphabet) for _ in range(length))


def random_password(length: int = 32) -> str:
    """Genera una contraseña segura sin caracteres problemáticos en URLs."""
    alphabet = string.ascii_letters + string.digits + "_-"
    return "".join(secrets.choice(alphabet) for _ in range(length))


def random_token(length: int = 40) -> str:
    return secrets.token_urlsafe(length)


def parse_example(path: str) -> list[tuple[str, str]]:
    """Lee .env.example y devuelve lista de (key, comment_or_value)."""
    lines = []
    with open(path) as f:
        for line in f:
            lines.append(line.rstrip("\n"))
    return lines


def generate(force: bool = False) -> None:
    if os.path.exists(ENV_PATH) and not force:
        print(f"⚠  Ya existe {ENV_PATH}. Usa --force para sobreescribir.")
        sys.exit(0)

    # Valores generados
    generated = {
        "SECRET_KEY": random_secret_key(50),
        "DB_PASS": random_password(24),
        "DB_NAME": "tarfia_socios",
        "DB_USER": "tarfia",
        "DB_HOST": "db",
        "DB_PORT": "5432",
        "DEBUG": "False",
        "ALLOWED_HOSTS": "tudominio.com,www.tudominio.com",
        "CORS_ALLOWED_ORIGINS": "https://tudominio.com",
        "API_BASE": "https://tudominio.com/api",
        "DJANGO_SETTINGS_MODULE": "config.settings.production",
        "EMAIL_BACKEND": "django.core.mail.backends.smtp.EmailBackend",
        "EMAIL_HOST": "smtp.gmail.com",
        "EMAIL_PORT": "587",
        "EMAIL_USE_TLS": "True",
        "EMAIL_HOST_USER": "",
        "EMAIL_HOST_PASSWORD": "",
        "DEFAULT_FROM_EMAIL": "noreply@tudominio.com",
        "GOOGLE_CLIENT_ID": "",
        "GOOGLE_CLIENT_SECRET": "",
        "GITHUB_CLIENT_ID": "",
        "GITHUB_CLIENT_SECRET": "",
    }

    # Construir el .env respetando la estructura del .env.example
    example_lines = parse_example(EXAMPLE_PATH)
    output_lines = []

    for line in example_lines:
        stripped = line.strip()
        # Comentario o línea vacía → se conserva tal cual
        if not stripped or stripped.startswith("#"):
            output_lines.append(line)
            continue

        key = stripped.split("=", 1)[0].strip()
        if key in generated:
            value = generated[key]
            output_lines.append(f"{key}={value}")
        else:
            output_lines.append(line)

    with open(ENV_PATH, "w") as f:
        f.write("\n".join(output_lines) + "\n")

    print(f"✓ {ENV_PATH} generado con secretos aleatorios.")
    print()
    print("  Valores generados automáticamente:")
    print(f"    SECRET_KEY  = {generated['SECRET_KEY'][:20]}…")
    print(f"    DB_PASS     = {generated['DB_PASS']}")
    print()
    print("  Recuerda configurar antes de arrancar:")
    print("    ALLOWED_HOSTS, CORS_ALLOWED_ORIGINS, API_BASE")
    print("    EMAIL_HOST_USER / EMAIL_HOST_PASSWORD")
    print("    GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET  (opcional)")
    print("    GITHUB_CLIENT_ID / GITHUB_CLIENT_SECRET  (opcional)")


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Genera .env con secretos aleatorios")
    parser.add_argument("--force", action="store_true", help="Sobreescribe .env si ya existe")
    args = parser.parse_args()
    generate(force=args.force)
