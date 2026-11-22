// Cena de cinema da pagina de apresentacao: conforme o visitante rola a pagina, o caderninho e escrito,
// vira bagunca (rasuras, cafe, interrogacoes) e se transforma no FiadoFacil no celular.
// Usa GSAP + ScrollTrigger (js/vendor). Sem eles, ou com "reduzir movimento", a cena fica parada.
(function () {
    var secao = document.querySelector('.cinema');
    if (!secao || !window.gsap || !window.ScrollTrigger) { return; }
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }
    gsap.registerPlugin(ScrollTrigger);
    secao.classList.add('cinema-ativo');

    var total = secao.querySelector('[data-total]');
    var valor = { v: 0 };
    function emReais(v) { return 'R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function legenda(n) { return '.legenda[data-cena="' + n + '"]'; }

    // estado inicial
    gsap.set('.legenda', { autoAlpha: 0, y: 30 });
    gsap.set(legenda(1), { autoAlpha: 1, y: 0 });
    gsap.set('.caderno-linha', { clipPath: 'inset(0 100% 0 0)' });
    gsap.set('.rasura path', { strokeDashoffset: 420 });
    gsap.set(['.mancha-cafe', '.interrogacao'], { autoAlpha: 0, scale: .4 });
    gsap.set('.celular', { autoAlpha: 0, y: 180, rotateX: 28, scale: .9 });
    gsap.set('.app-item', { autoAlpha: 0, x: 50 });
    gsap.set(['.app-aviso', '.app-recebido'], { autoAlpha: 0, y: 24, scale: .9 });
    gsap.set('.app-limite-barra', { scaleX: 0 });

    // A cena anda junto com a rolagem: descendo a pagina ela avanca, subindo ela volta, parando ela para.
    // O palco fica grudado na tela (CSS sticky) enquanto a secao passa; a rolagem nunca e bloqueada.
    secao.classList.add('cinema-rolagem');
    var cena = gsap.timeline({ defaults: { ease: 'none' }, scrollTrigger: { trigger: secao, start: 'top top', end: 'bottom bottom', scrub: 0.6 } });
    cena
        // 1) o caderninho sendo escrito, linha por linha
        .to('.caderno-linha', { clipPath: 'inset(0 0% 0 0)', duration: 1, stagger: .35, ease: 'power1.out' })
        // 2) a bagunca: rasuras, cafe, interrogacoes e a soma que nao fecha
        .to(legenda(1), { autoAlpha: 0, y: -30, duration: .5 }, '+=.8')
        .to(legenda(2), { autoAlpha: 1, y: 0, duration: .4 }, '<.2')
        .to('.rasura path', { strokeDashoffset: 0, duration: .8, stagger: .25 }, '<')
        .to('.mancha-cafe', { autoAlpha: 1, scale: 1, duration: .6, ease: 'back.out(1.6)' }, '<.3')
        .to('.interrogacao', { autoAlpha: 1, scale: 1, duration: .5, stagger: .2, ease: 'back.out(2.4)' }, '<')
        .to('.caderno', { rotate: -10, duration: .6, ease: 'power1.inOut' }, '<')
        // 3) o caderninho vira sistema: a luz muda e o celular sobe
        .to(legenda(2), { autoAlpha: 0, y: -30, duration: .5 }, '+=.8')
        .to(legenda(3), { autoAlpha: 1, y: 0, duration: .4 }, '<.2')
        .to('.cinema-palco', { backgroundColor: '#0d1b36', color: '#f1f5f9', duration: 1 }, '<')
        .to('.caderno', { scale: .3, rotate: 28, y: -120, autoAlpha: 0, duration: 1, ease: 'power2.in' }, '<')
        .to('.celular', { autoAlpha: 1, y: 0, rotateX: 0, scale: 1, duration: 1, ease: 'power2.out' }, '<.5')
        .to('.app-item', { autoAlpha: 1, x: 0, duration: .5, stagger: .2, ease: 'power2.out' }, '<.5')
        .to(valor, { v: 1240, duration: 1, ease: 'power1.out', onUpdate: function () { total.textContent = emReais(valor.v); } }, '<')
        // 4) as regras trabalhando: limite, bloqueio e pagamento registrado
        .to(legenda(3), { autoAlpha: 0, y: -30, duration: .5 }, '+=.8')
        .to(legenda(4), { autoAlpha: 1, y: 0, duration: .4 }, '<.2')
        .to('.app-limite-barra', { scaleX: .92, duration: .8, ease: 'power2.out' }, '<')
        .to('.app-aviso', { autoAlpha: 1, y: 0, scale: 1, duration: .4, ease: 'back.out(2)' }, '>-.1')
        .to('.app-recebido', { autoAlpha: 1, y: 0, scale: 1, duration: .4, ease: 'back.out(2)' }, '+=.3')
        .to('.celular', { rotateY: -10, rotateX: 5, duration: .8, ease: 'sine.inOut' }, '<')
        .to({}, { duration: 1 });

    // as bolhas do fundo da abertura andam em velocidades diferentes (profundidade)
    gsap.to('.bolha-1', { yPercent: 60, ease: 'none', scrollTrigger: { trigger: '.inicio-hero', start: 'top top', end: 'bottom top', scrub: true } });
    gsap.to('.bolha-2', { yPercent: -40, ease: 'none', scrollTrigger: { trigger: '.inicio-hero', start: 'top top', end: 'bottom top', scrub: true } });
})();
