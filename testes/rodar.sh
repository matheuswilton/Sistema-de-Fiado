#!/usr/bin/env bash
# Roda todos os testes. O site de testes precisa estar ligado em http://127.0.0.1:8080
# (veja testes/README.md). Sai com erro se qualquer teste falhar.
cd "$(dirname "$0")"
resultado=0
php unidade.php || resultado=1
for teste in regras conta_cpf api isolamento; do
    echo ""
    echo "== $teste =="
    node "$teste.js" || resultado=1
done
echo ""
if [ "$resultado" -eq 0 ]; then echo "TODOS OS TESTES PASSARAM"; else echo "ALGUM TESTE FALHOU"; fi
exit $resultado
