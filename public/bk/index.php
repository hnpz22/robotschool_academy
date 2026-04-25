<?php
// public/index.php &mdash; P&aacute;gina web p&uacute;blica ROBOTSchool Academy Learning
// Conserva todo el contenido de ROBOTSchool_Web_v3.html
// + secci&oacute;n de cursos din&aacute;micos desde la BD
require_once __DIR__ . '/../config/config.php';

// Cursos publicados con grupos disponibles
$cursos = $pdo->query("
    SELECT c.*, s.nombre AS sede_nombre, s.ciudad AS sede_ciudad,
        (SELECT COUNT(*) FROM grupos g WHERE g.curso_id = c.id AND g.activo = 1) AS total_grupos,
        (SELECT COALESCE(SUM(g.cupo_real) - COUNT(m.id), 0)
         FROM grupos g LEFT JOIN matriculas m ON m.grupo_id = g.id AND m.estado = 'activa'
         WHERE g.curso_id = c.id AND g.activo = 1) AS total_disponibles
    FROM cursos c JOIN sedes s ON s.id = c.sede_id
    WHERE c.publicado = 1
    ORDER BY c.orden ASC, c.id ASC
")->fetchAll();

$sedes_pub   = $pdo->query("SELECT * FROM sedes WHERE activa=1 ORDER BY nombre")->fetchAll();
$filtro_sede = $_GET['sede'] ?? '';
$filtro_edad = $_GET['edad'] ?? '';

$cursos_filtrados = array_filter($cursos, function($c) use ($filtro_sede, $filtro_edad) {
    if ($filtro_sede && $c['sede_id'] != $filtro_sede) return false;
    if ($filtro_edad) {
        [$emin,$emax] = explode('-', $filtro_edad);
        if ($c['edad_min'] > (int)$emax || $c['edad_max'] < (int)$emin) return false;
    }
    return true;
});
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>ROBOTSchool &ndash; Ecosistema de Innovaci&oacute;n Educativa Tecnol&oacute;gica</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet"/>
<style>
/* &#9552;&#9552; ADN visual ROBOTSchool (id&eacute;ntico a Web_v3) &#9552;&#9552; */
:root{--orange:#F26522;--orange-d:#d4541a;--blue:#1E4DA1;--blue-d:#163a80;--lblue:#2E9CCA;--yellow:#FFCA28;--dark:#0f1623;--dark2:#1A1A2E;--gray:#F5F7FA;--text:#1a2234;--muted:#64748b;--border:#e0e6f0;--white:#ffffff;--grad-hero:linear-gradient(135deg,#0f1623 0%,#1E4DA1 60%,#2E9CCA 100%);--grad-orange:linear-gradient(135deg,#F26522,#ff8c42);--shadow-sm:0 2px 16px rgba(0,0,0,.07);--shadow-md:0 6px 32px rgba(0,0,0,.11);--shadow-lg:0 16px 60px rgba(0,0,0,.16);--r:16px;--r-sm:10px}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:'Nunito',sans-serif;color:var(--text);background:#fff;overflow-x:hidden}
h1,h2,h3,h4,h5,h6{font-family:'Poppins',sans-serif;font-weight:700}
::-webkit-scrollbar{width:6px}::-webkit-scrollbar-track{background:#f0f0f0}::-webkit-scrollbar-thumb{background:var(--orange);border-radius:3px}

/* TOPBAR */
.topbar{background:var(--blue);color:#fff;font-size:.8rem;padding:.4rem 0;position:relative;z-index:200}
.topbar a{color:rgba(255,255,255,.85);text-decoration:none;margin:0 .4rem;transition:color .2s}
.topbar a:hover{color:var(--yellow)}
.topbar .separator{opacity:.35;margin:0 .3rem}

/* NAVBAR */
.navbar{background:rgba(255,255,255,.97);backdrop-filter:blur(20px);border-bottom:3px solid var(--orange);padding:.55rem 0;box-shadow:0 4px 24px rgba(0,0,0,.09);position:sticky;top:0;z-index:100;transition:all .3s}
.navbar.scrolled{background:rgba(255,255,255,.98);box-shadow:0 6px 32px rgba(0,0,0,.14)}
.navbar-brand img{height:52px;transition:transform .3s}
.navbar-brand:hover img{transform:scale(1.04)}
.nav-link{color:var(--dark2)!important;font-weight:700;font-size:.88rem;padding:.45rem .8rem!important;position:relative;transition:color .2s}
.nav-link::after{content:'';position:absolute;bottom:-2px;left:.8rem;right:.8rem;height:2px;background:var(--orange);transform:scaleX(0);transition:transform .25s;border-radius:1px}
.nav-link:hover::after,.nav-link.active::after{transform:scaleX(1)}
.nav-link:hover,.nav-link.active{color:var(--orange)!important}
.btn-nav-cta{background:var(--orange)!important;color:#fff!important;border-radius:25px;padding:.42rem 1.3rem!important;font-weight:800!important;font-size:.85rem!important;transition:all .2s!important;box-shadow:0 4px 16px rgba(242,101,34,.3)!important}
.btn-nav-cta:hover{background:var(--orange-d)!important;transform:translateY(-2px)!important}
.btn-nav-cta::after{display:none!important}

/* HERO */
.hero-wrap{position:relative;overflow:hidden}
#heroSlider .carousel-item{height:92vh;min-height:580px;max-height:800px;position:relative}
.slide-bg{width:100%;height:100%;object-fit:cover;filter:brightness(.42) saturate(1.1);transform:scale(1.05);transition:transform 8s ease}
.carousel-item.active .slide-bg{transform:scale(1)}
.slide-overlay{position:absolute;inset:0;background:linear-gradient(to right,rgba(15,22,35,.88) 0%,rgba(15,22,35,.5) 60%,rgba(15,22,35,.1) 100%)}
.slide-content{position:absolute;inset:0;display:flex;flex-direction:column;justify-content:center;align-items:flex-start;padding:0 8%;max-width:780px}
.slide-eyebrow{display:inline-flex;align-items:center;gap:.5rem;background:var(--orange);color:#fff;font-size:.72rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;padding:.28rem 1rem;border-radius:20px;margin-bottom:1.2rem;animation:fadeInDown .6s ease .2s both}
.slide-title{font-size:clamp(2rem,5vw,3.6rem);font-weight:900;color:#fff;line-height:1.12;margin-bottom:1rem;text-shadow:0 3px 16px rgba(0,0,0,.45);animation:fadeInLeft .7s ease .35s both}
.slide-title .accent{color:var(--yellow)}
.slide-sub{color:rgba(255,255,255,.82);font-size:1.05rem;max-width:520px;line-height:1.75;margin-bottom:2rem;animation:fadeInLeft .7s ease .5s both}
.slide-btns{display:flex;gap:1rem;flex-wrap:wrap;animation:fadeInUp .6s ease .65s both}
.btn-hero-primary{background:var(--orange);color:#fff;border:none;border-radius:28px;padding:.72rem 2.2rem;font-weight:800;font-size:.95rem;text-decoration:none;transition:all .25s;box-shadow:0 6px 24px rgba(242,101,34,.45);display:inline-flex;align-items:center;gap:.5rem}
.btn-hero-primary:hover{background:var(--orange-d);color:#fff;transform:translateY(-3px);box-shadow:0 12px 36px rgba(242,101,34,.5)}
.btn-hero-ghost{background:rgba(255,255,255,.12);backdrop-filter:blur(8px);border:2px solid rgba(255,255,255,.55);color:#fff;border-radius:28px;padding:.7rem 2rem;font-weight:700;font-size:.95rem;text-decoration:none;transition:all .25s;display:inline-flex;align-items:center;gap:.5rem}
.btn-hero-ghost:hover{background:rgba(255,255,255,.22);color:#fff;transform:translateY(-3px)}
.carousel-control-prev,.carousel-control-next{width:52px;height:52px;background:rgba(255,255,255,.15);backdrop-filter:blur(8px);border-radius:50%;top:50%;transform:translateY(-50%);opacity:0;transition:opacity .3s}
.hero-wrap:hover .carousel-control-prev,.hero-wrap:hover .carousel-control-next{opacity:1}
.carousel-control-prev{left:2rem}.carousel-control-next{right:2rem}

/* HERO STATS BAR */
.hero-stats-bar{position:absolute;bottom:0;left:0;right:0;background:rgba(15,22,35,.85);backdrop-filter:blur(12px)}
.hstat{padding:.9rem 1.5rem;border-right:1px solid rgba(255,255,255,.08);text-align:center}
.hstat:last-child{border-right:none}
.hnum{font-family:'Poppins',sans-serif;font-size:2rem;font-weight:900;color:#fff;line-height:1}
.hlbl{font-size:.72rem;color:rgba(255,255,255,.55);text-transform:uppercase;letter-spacing:.08em;margin-top:.2rem}
.stats-strip .stat-box{padding:1rem;border-right:1px solid var(--border);text-align:center}
.stats-strip .stat-box:last-child{border-right:none}
.stats-strip .num{font-family:'Poppins',sans-serif;font-size:1.8rem;font-weight:900;color:var(--blue)}
.stats-strip .lbl{font-size:.75rem;color:var(--muted);text-transform:uppercase}

/* SECTIONS */
section{padding:5rem 0}
.bg-gray{background:var(--gray)}
.sec-title{font-size:clamp(1.6rem,3vw,2.3rem);font-weight:800;color:var(--text)}
.orange-line{display:block;width:48px;height:4px;background:var(--orange);border-radius:2px;margin:.8rem 0 1.2rem}
.orange-line.center{margin:.8rem auto 1.2rem}
.sec-sub{color:var(--muted);font-size:.97rem;max-width:600px;margin:0 auto}

/* ABOUT */
.about-section{padding:5rem 0}
.about-img-wrap{position:relative}
.about-img-main{width:100%;border-radius:var(--r);box-shadow:var(--shadow-lg)}
.about-img-float{position:absolute;bottom:-2rem;right:-2rem;width:55%;border-radius:var(--r-sm);box-shadow:var(--shadow-lg);border:4px solid #fff}
.about-badge{display:inline-flex;align-items:center;gap:.5rem;background:rgba(242,101,34,.1);color:var(--orange);font-size:.78rem;font-weight:800;padding:.3rem .9rem;border-radius:20px;margin-bottom:1rem;border:1px solid rgba(242,101,34,.2)}
.checkitem{display:flex;gap:.75rem;margin-bottom:.75rem;font-size:.92rem;line-height:1.6}
.ci-dot{width:22px;height:22px;background:var(--orange);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:900;flex-shrink:0;margin-top:2px}
.btn-card{display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1.2rem;background:#fff;color:var(--blue);border:1.5px solid var(--border);border-radius:var(--r-sm);font-weight:700;font-size:.85rem;text-decoration:none;transition:all .2s}
.btn-card:hover{border-color:var(--orange);color:var(--orange);transform:translateY(-2px)}

/* PEDAGOGY */
.ped-strip{background:var(--dark2);padding:3rem 0}
.ped-item{padding:1.5rem 1rem;border-right:1px solid rgba(255,255,255,.08)}
.ped-item:last-child{border-right:none}
.ped-icon{width:52px;height:52px;background:rgba(242,101,34,.15);color:var(--orange);border-radius:var(--r-sm);display:flex;align-items:center;justify-content:center;font-size:1.3rem;margin-bottom:.9rem}
.ped-item h5{color:#fff;font-size:.97rem;margin-bottom:.5rem}
.ped-item p{color:rgba(255,255,255,.55);font-size:.82rem;line-height:1.6}

/* PROGRAMS */
.prog-card{background:#fff;border-radius:var(--r);box-shadow:var(--shadow-sm);overflow:hidden;transition:all .3s;height:100%}
.prog-card:hover{transform:translateY(-6px);box-shadow:var(--shadow-lg)}
.prog-card-img-wrap{overflow:hidden;height:200px}
.prog-card-img{width:100%;height:100%;object-fit:cover;transition:transform .4s}
.prog-card:hover .prog-card-img{transform:scale(1.06)}
.prog-card-body{padding:1.4rem}
.age-badge{display:inline-block;background:var(--orange);color:#fff;font-size:.68rem;font-weight:800;padding:.22rem .7rem;border-radius:20px;margin-bottom:.7rem}
.prog-card-body h5{font-size:1rem;font-weight:800;margin-bottom:.5rem;color:var(--text)}
.prog-card-body p{font-size:.84rem;color:var(--muted);line-height:1.65;margin-bottom:1rem}

/* ECOSYSTEM */
.eco-card{background:#fff;border-radius:var(--r);padding:1.6rem;box-shadow:var(--shadow-sm);border-left:4px solid var(--orange);transition:all .25s}
.eco-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-md)}
.eco-icon{width:48px;height:48px;background:rgba(242,101,34,.1);color:var(--orange);border-radius:var(--r-sm);display:flex;align-items:center;justify-content:center;font-size:1.2rem;margin-bottom:1rem}
.eco-card h5{font-size:.97rem;font-weight:800;margin-bottom:.4rem}
.eco-card p{font-size:.83rem;color:var(--muted);line-height:1.6}

/* DIPLOMADOS */
.dip-card{background:linear-gradient(135deg,var(--dark) 0%,#1a2e5a 100%);border-radius:var(--r);padding:1.8rem;color:#fff;position:relative;overflow:hidden;transition:transform .25s}
.dip-card:hover{transform:translateY(-4px)}
.dip-card::before{content:'';position:absolute;top:-30px;right:-30px;width:120px;height:120px;background:rgba(242,101,34,.12);border-radius:50%}
.dip-badge{display:inline-block;background:var(--orange);color:#fff;font-size:.68rem;font-weight:800;padding:.2rem .7rem;border-radius:20px;margin-bottom:.8rem}
.dip-card h5{font-size:1rem;font-weight:800;margin-bottom:.5rem}
.dip-card p{font-size:.82rem;color:rgba(255,255,255,.65);line-height:1.6;margin-bottom:1rem}
.dip-hours{font-size:.75rem;color:rgba(255,255,255,.5);display:flex;align-items:center;gap:.3rem}

/* COLEGIOS */
.col-feature{display:flex;gap:1rem;margin-bottom:1.5rem}
.col-feature-icon{width:44px;height:44px;background:rgba(242,101,34,.1);color:var(--orange);border-radius:var(--r-sm);display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0}
.col-feature h6{font-size:.92rem;font-weight:800;margin-bottom:.25rem}
.col-feature p{font-size:.83rem;color:var(--muted);line-height:1.55}
.col-img{width:100%;border-radius:var(--r);box-shadow:var(--shadow-lg)}

/* LATAM */
.latam-section{background:linear-gradient(135deg,var(--dark2) 0%,#0d1f3c 100%);padding:5rem 0;color:#fff}
.kpi-card{text-align:center;padding:1.5rem;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:var(--r)}
.kpi-num{font-family:'Poppins',sans-serif;font-size:2.2rem;font-weight:900;color:var(--orange)}
.kpi-lbl{font-size:.8rem;color:rgba(255,255,255,.7);font-weight:700;margin:.3rem 0}
.kpi-note{font-size:.72rem;color:rgba(255,255,255,.4)}
.country-bar{display:flex;align-items:center;gap:1rem;margin-bottom:.75rem}
.country-name{min-width:90px;font-size:.85rem;color:rgba(255,255,255,.75)}
.bar-track{flex:1;height:28px;background:rgba(255,255,255,.06);border-radius:6px;overflow:hidden}
.bar-fill{height:100%;display:flex;align-items:center;padding:0 .7rem;font-size:.72rem;font-weight:700;color:#fff;border-radius:6px;width:0;transition:width 1.2s cubic-bezier(.22,.61,.36,1)}
.bf-orange{background:linear-gradient(90deg,var(--orange),#ff8c42)}
.bf-blue{background:linear-gradient(90deg,var(--blue),var(--lblue))}
.bf-teal{background:linear-gradient(90deg,#1DA99A,#5DCAA5)}
.proj-table{color:rgba(255,255,255,.75);font-size:.85rem}
.proj-table thead th{color:rgba(255,255,255,.5);font-size:.75rem;font-weight:700;text-transform:uppercase;border-bottom:1px solid rgba(255,255,255,.1);padding:.6rem .4rem}
.proj-table tbody td{padding:.6rem .4rem;border-bottom:1px solid rgba(255,255,255,.06)}
.td-hl{color:var(--orange);font-weight:800}

/* ALLIES */
.ally-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:1rem}
.ally-item{background:#fff;border-radius:var(--r-sm);padding:.8rem;display:flex;align-items:center;justify-content:center;box-shadow:var(--shadow-sm);transition:all .25s}
.ally-item:hover{transform:translateY(-3px);box-shadow:var(--shadow-md)}
.ally-item img{max-height:50px;width:100%;object-fit:contain;filter:grayscale(40%);transition:filter .3s}
.ally-item:hover img{filter:grayscale(0%)}

/* TESTIMONIALS */
.test-card{background:#fff;border-radius:var(--r);padding:2rem;box-shadow:var(--shadow-sm);border-top:3px solid var(--orange);height:100%}
.test-card .stars{color:var(--orange);font-size:1rem;margin-bottom:.8rem}
.test-card blockquote{font-size:.88rem;color:var(--muted);line-height:1.75;font-style:italic;margin-bottom:1rem;border:none;padding:0}
.test-card .name{font-weight:800;color:var(--text);font-size:.92rem}
.test-card .role{font-size:.78rem;color:var(--muted)}

/* &#9552;&#9552;&#9552; SECCI&Oacute;N CURSOS DIN&Aacute;MICOS &#9552;&#9552;&#9552; */
.filtros-wrap{background:#fff;border-bottom:1px solid var(--border);padding:.9rem 0;position:sticky;top:74px;z-index:90;box-shadow:0 4px 16px rgba(0,0,0,.04)}
.filtro-pill{display:inline-flex;align-items:center;gap:.35rem;padding:.38rem 1rem;border-radius:25px;border:1.5px solid var(--border);background:#fff;font-size:.8rem;font-weight:700;color:var(--muted);cursor:pointer;transition:all .2s;text-decoration:none;margin:.2rem .2rem}
.filtro-pill:hover,.filtro-pill.on{background:var(--orange);color:#fff;border-color:var(--orange)}
.curso-pub-card{background:#fff;border-radius:var(--r);border:1px solid var(--border);overflow:hidden;height:100%;display:flex;flex-direction:column;transition:all .3s}
.curso-pub-card:hover{transform:translateY(-6px);box-shadow:var(--shadow-lg);border-color:var(--orange)}
.curso-pub-img{width:100%;height:200px;object-fit:cover}
.curso-pub-placeholder{width:100%;height:200px;background:var(--grad-hero);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.15);font-size:3.5rem}
.curso-pub-body{padding:1.3rem;flex:1;display:flex;flex-direction:column}
.btn-inscribir{display:flex;align-items:center;justify-content:center;gap:.4rem;padding:.65rem;background:var(--orange);color:#fff;border-radius:var(--r-sm);font-weight:800;font-size:.88rem;text-decoration:none;transition:all .25s;box-shadow:0 4px 16px rgba(242,101,34,.3);margin-top:auto}
.btn-inscribir:hover{background:var(--orange-d);color:#fff;transform:translateY(-2px)}
.horario-row{display:flex;align-items:center;gap:.5rem;padding:.25rem 0;border-bottom:1px solid var(--border);font-size:.75rem}
.horario-row:last-child{border-bottom:none}

/* CTA */
.cta-section{background:var(--grad-orange);padding:4.5rem 0;position:relative;overflow:hidden;text-align:center}
.cta-section::before{content:'';position:absolute;inset:0;background-image:radial-gradient(circle,rgba(255,255,255,.08) 1px,transparent 1px);background-size:24px 24px}
.cta-section h2{color:#fff;font-size:clamp(1.6rem,3vw,2.5rem);margin-bottom:1rem}
.cta-section p{color:rgba(255,255,255,.85);font-size:1rem;max-width:580px;margin:0 auto 2rem}
.btn-cta-white{background:#fff;color:var(--orange);border-radius:28px;padding:.75rem 2.2rem;font-weight:800;font-size:.97rem;text-decoration:none;transition:all .25s;display:inline-flex;align-items:center;gap:.5rem;box-shadow:0 6px 24px rgba(0,0,0,.15);margin:.3rem}
.btn-cta-white:hover{transform:translateY(-3px);box-shadow:0 12px 36px rgba(0,0,0,.22);color:var(--orange-d)}
.btn-cta-outline{background:transparent;color:#fff;border:2px solid rgba(255,255,255,.7);border-radius:28px;padding:.73rem 2rem;font-weight:700;font-size:.97rem;text-decoration:none;transition:all .25s;display:inline-flex;align-items:center;gap:.5rem;margin:.3rem}
.btn-cta-outline:hover{background:rgba(255,255,255,.15);color:#fff;transform:translateY(-3px)}

/* FOOTER */
footer{background:var(--dark);color:rgba(255,255,255,.72);padding:4rem 0 1.5rem;font-size:.9rem}
.footer-logo img{height:48px;margin-bottom:1rem;filter:brightness(0) invert(1)}
.footer-title{color:#fff;font-weight:800;font-size:.97rem;margin-bottom:1rem}
.footer-links{list-style:none;padding:0}
.footer-links li{margin-bottom:.42rem}
.footer-links a{color:rgba(255,255,255,.6);text-decoration:none;transition:all .2s;display:inline-flex;align-items:center;gap:.4rem}
.footer-links a::before{content:'\203A';color:var(--orange);font-weight:900}
.footer-links a:hover{color:var(--orange);padding-left:4px}
.social-btn{display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,.1);color:#fff;font-size:1rem;text-decoration:none;margin-right:.4rem;transition:all .2s}
.social-btn:hover{background:var(--orange);color:#fff;transform:translateY(-3px)}
.contact-info{display:flex;align-items:flex-start;gap:.6rem;margin-bottom:.65rem;color:rgba(255,255,255,.65);font-size:.88rem}
.contact-info i{color:var(--orange);flex-shrink:0;margin-top:2px}
.footer-divider{border-color:rgba(255,255,255,.08)}

/* FLOATING */
.wa-float{position:fixed;bottom:28px;right:28px;z-index:999;width:56px;height:56px;background:#25D366;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.7rem;text-decoration:none;box-shadow:0 6px 24px rgba(37,211,102,.45);animation:float 3s ease-in-out infinite;transition:transform .2s}
.wa-float:hover{transform:scale(1.12);color:#fff}
.back-top{position:fixed;bottom:28px;left:28px;z-index:999;width:42px;height:42px;border-radius:50%;background:var(--blue);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.1rem;text-decoration:none;box-shadow:var(--shadow-md);opacity:0;pointer-events:none;transition:all .3s}
.back-top.show{opacity:1;pointer-events:auto}
.back-top:hover{background:var(--orange);color:#fff;transform:translateY(-3px)}

/* ANIMATIONS */
.reveal{opacity:0;transform:translateY(36px);transition:opacity .65s ease,transform .65s ease}
.reveal.from-left{transform:translateX(-40px)}.reveal.from-right{transform:translateX(40px)}
.reveal.visible{opacity:1!important;transform:translate(0)!important}
.stagger>*{opacity:0;transform:translateY(24px);transition:opacity .5s ease,transform .5s ease}
.stagger.visible>*:nth-child(1){transition-delay:.05s}.stagger.visible>*:nth-child(2){transition-delay:.15s}.stagger.visible>*:nth-child(3){transition-delay:.25s}.stagger.visible>*:nth-child(4){transition-delay:.35s}.stagger.visible>*:nth-child(5){transition-delay:.45s}.stagger.visible>*:nth-child(6){transition-delay:.55s}
.stagger.visible>*{opacity:1;transform:translateY(0)}
@keyframes fadeInDown{from{opacity:0;transform:translateY(-20px)}to{opacity:1;transform:translateY(0)}}
@keyframes fadeInLeft{from{opacity:0;transform:translateX(-30px)}to{opacity:1;transform:translateX(0)}}
@keyframes fadeInUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}

@media(max-width:768px){
  #heroSlider .carousel-item{height:75vh;min-height:460px}
  .slide-title{font-size:1.9rem}
  .about-img-float{display:none}
  .hstat{padding:.3rem .5rem}.hstat .hnum{font-size:1.4rem}
  section{padding:4rem 0}
}
</style>
</head>
<body>

<a href="https://wa.link/ktfv4o" target="_blank" class="wa-float" title="WhatsApp"><i class="bi bi-whatsapp"></i></a>
<a href="#top" class="back-top" id="backTop" title="Volver arriba"><i class="bi bi-arrow-up"></i></a>

<!-- TOPBAR -->
<div class="topbar" id="top">
  <div class="container d-flex justify-content-between align-items-center flex-wrap gap-1">
    <div>
      <i class="bi bi-geo-alt-fill me-1"></i>Bogot&aacute; &middot; Cali
      <span class="separator">|</span>
      <i class="bi bi-telephone-fill me-1"></i><a href="tel:+573186541859">(57) 318 654 1859</a>
      <span class="separator">|</span>
      <i class="bi bi-envelope-fill me-1"></i><a href="mailto:info@robotschool.com.co">info@robotschool.com.co</a>
    </div>
    <div>
      <a href="https://www.facebook.com/RobotSchoolEdu/" target="_blank"><i class="bi bi-facebook"></i></a>
      <a href="https://www.instagram.com/robotschoolco/" target="_blank"><i class="bi bi-instagram"></i></a>
      <a href="https://www.youtube.com/c/ROBOTSchoolco" target="_blank"><i class="bi bi-youtube"></i></a>
      <a href="https://wa.link/ktfv4o" target="_blank"><i class="bi bi-whatsapp"></i></a>
    </div>
  </div>
</div>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg" id="mainNav">
  <div class="container">
    <a class="navbar-brand" href="#">
      <img src="https://wsrv.nl/?url=https%3A%2F%2Frobotschool.com.co%2Fwp-content%2Fuploads%2F2022%2F07%2F02-RobotSchool-Escuela-de-Robotica-1.png&w=300&q=90" alt="ROBOTSchool"/>
    </a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto align-items-center gap-1">
        <li class="nav-item"><a class="nav-link active" href="#">Inicio</a></li>
        <li class="nav-item"><a class="nav-link" href="#nosotros">Nosotros</a></li>
        <li class="nav-item"><a class="nav-link" href="#academias">Academias</a></li>
        <li class="nav-item"><a class="nav-link" href="#programas">Programas</a></li>
        <li class="nav-item"><a class="nav-link" href="#ecosistema">Ecosistema</a></li>
        <li class="nav-item"><a class="nav-link" href="#diplomas">Diplomados</a></li>
        <li class="nav-item"><a class="nav-link" href="#colegios">Colegios</a></li>
        <li class="nav-item"><a class="nav-link" href="#aliados">Aliados</a></li>
        <li class="nav-item ms-2">
          <a class="nav-link btn-nav-cta" href="#contacto"><i class="bi bi-envelope-fill me-1"></i>Cont&aacute;ctanos</a>
        </li>
        <li class="nav-item">
          <a href="<?= BASE_URL ?>login.php" class="nav-link" style="font-size:.8rem;color:var(--muted)!important;">
            <i class="bi bi-lock-fill"></i>
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- HERO SLIDER -->
<div class="hero-wrap">
  <div id="heroSlider" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5500">
    <div class="carousel-indicators">
      <button type="button" data-bs-target="#heroSlider" data-bs-slide-to="0" class="active"></button>
      <button type="button" data-bs-target="#heroSlider" data-bs-slide-to="1"></button>
      <button type="button" data-bs-target="#heroSlider" data-bs-slide-to="2"></button>
      <button type="button" data-bs-target="#heroSlider" data-bs-slide-to="3"></button>
    </div>
    <div class="carousel-inner">
      <div class="carousel-item active">
        <img class="slide-bg" src="https://wsrv.nl/?url=https%3A%2F%2Frobotschool.com.co%2Fwp-content%2Fuploads%2F2022%2F07%2F04-RobotSchool-Escuela-de-Robotica01-4-1024x576.jpg&w=1200&q=85" alt="ROBOTSchool"/>
        <div class="slide-overlay"></div>
        <div class="slide-content">
          <span class="slide-eyebrow"><i class="bi bi-rocket-takeoff-fill"></i>Desde 2014 &middot; Colombia</span>
          <h1 class="slide-title">Ecosistema de Innovaci&oacute;n<br/><span class="accent">Educativa Tecnol&oacute;gica</span></h1>
          <p class="slide-sub">Transformamos la ense&ntilde;anza en colegios mediante rob&oacute;tica, programaci&oacute;n, pensamiento computacional y cultura maker.</p>
          <div class="slide-btns">
            <a href="#academias" class="btn-hero-primary"><i class="bi bi-grid-fill"></i>Ver Programas</a>
            <a href="#nosotros" class="btn-hero-ghost"><i class="bi bi-play-circle-fill"></i>Con&oacute;cenos</a>
          </div>
        </div>
      </div>
      <div class="carousel-item">
        <img class="slide-bg" src="https://wsrv.nl/?url=https%3A%2F%2Frobotschool.com.co%2Fwp-content%2Fuploads%2F2022%2F07%2F04-RobotSchool-Escuela-de-Robotica01-2-1024x576.jpg&w=1200&q=85" alt="Rob&oacute;tica"/>
        <div class="slide-overlay"></div>
        <div class="slide-content">
          <span class="slide-eyebrow"><i class="bi bi-mortarboard-fill"></i>Rob&oacute;tica para Todos</span>
          <h1 class="slide-title">&iexcl;Convi&eacute;rtete en un<br/><span class="accent">Experto en Rob&oacute;tica!</span></h1>
          <p class="slide-sub">Ni&ntilde;os, j&oacute;venes y adultos aprenden a innovar y construir soluciones tecnol&oacute;gicas con LEGO, Arduino, ESP32 y m&aacute;s.</p>
          <div class="slide-btns">
            <a href="#academias" class="btn-hero-primary"><i class="bi bi-star-fill"></i>Ver Cursos</a>
            <a href="#contacto" class="btn-hero-ghost"><i class="bi bi-whatsapp"></i>WhatsApp</a>
          </div>
        </div>
      </div>
      <div class="carousel-item">
        <img class="slide-bg" src="https://wsrv.nl/?url=https%3A%2F%2Frobotschool.com.co%2Fwp-content%2Fuploads%2F2022%2F07%2F04-RobotSchool-Escuela-de-Robotica01-5-1024x576.jpg&w=1200&q=85" alt="Colegios"/>
        <div class="slide-overlay"></div>
        <div class="slide-content">
          <span class="slide-eyebrow"><i class="bi bi-building-fill"></i>Para Colegios e Instituciones</span>
          <h1 class="slide-title">Maker Spaces,<br/><span class="accent">Laboratorios & STEM</span></h1>
          <p class="slide-sub">Implementamos laboratorios tecnol&oacute;gicos, formamos docentes y acompa&ntilde;amos a los colegios en su transformaci&oacute;n digital.</p>
          <div class="slide-btns">
            <a href="#colegios" class="btn-hero-primary"><i class="bi bi-building"></i>Soy un Colegio</a>
            <a href="#ecosistema" class="btn-hero-ghost"><i class="bi bi-info-circle"></i>El Ecosistema</a>
          </div>
        </div>
      </div>
      <div class="carousel-item">
        <img class="slide-bg" src="https://wsrv.nl/?url=https%3A%2F%2Frobotschool.com.co%2Fwp-content%2Fuploads%2F2022%2F07%2FEscuela-de-Robotica-Robot-School-2-1024x576.jpg&w=1200&q=85" alt="Diplomados"/>
        <div class="slide-overlay"></div>
        <div class="slide-content">
          <span class="slide-eyebrow"><i class="bi bi-award-fill"></i>Formaci&oacute;n Docente</span>
          <h1 class="slide-title">Diplomados<br/><span class="accent">para Educadores</span></h1>
          <p class="slide-sub">Rob&oacute;tica, programaci&oacute;n, STEM e Inteligencia Artificial aplicada al aula. Formamos a quienes forman a las nuevas generaciones.</p>
          <div class="slide-btns">
            <a href="#diplomas" class="btn-hero-primary"><i class="bi bi-award"></i>Ver Diplomados</a>
            <a href="#contacto" class="btn-hero-ghost"><i class="bi bi-envelope"></i>Escr&iacute;benos</a>
          </div>
        </div>
      </div>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#heroSlider" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroSlider" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
    <div class="hero-stats-bar d-none d-md-block">
      <div class="container">
        <div class="row g-0">
          <div class="col-3 hstat"><div class="hnum">500<sup style="font-size:1rem">+</sup></div><div class="hlbl">Estudiantes semestrales</div></div>
          <div class="col-3 hstat"><div class="hnum">2.000<sup style="font-size:1rem">+</sup></div><div class="hlbl">Cursos libres</div></div>
          <div class="col-3 hstat"><div class="hnum">70<sup style="font-size:1rem">+</sup></div><div class="hlbl">Labs & Maker Spaces</div></div>
          <div class="col-3 hstat"><div class="hnum">30<sup style="font-size:1rem">+</sup></div><div class="hlbl">Colegios aliados</div></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- STATS MOBILE -->
<div class="stats-strip d-md-none">
  <div class="container"><div class="row g-0">
    <div class="col-6 stat-box"><div class="num">500<sup>+</sup></div><div class="lbl">Estudiantes</div></div>
    <div class="col-6 stat-box"><div class="num">70<sup>+</sup></div><div class="lbl">Labs STEAM</div></div>
  </div></div>
</div>

<!-- NOSOTROS -->
<section id="nosotros" class="about-section">
  <div class="container">
    <div class="row align-items-center gy-5">
      <div class="col-lg-6 reveal from-left">
        <div class="about-img-wrap pe-lg-3">
          <img class="about-img-main" src="https://wsrv.nl/?url=https%3A%2F%2Frobotschool.com.co%2Fwp-content%2Fuploads%2F2022%2F07%2FEscuela-de-Robotica-Robot-School-3-1024x576.jpg&w=1200&q=85" alt="ROBOTSchool"/>
          <img class="about-img-float d-none d-lg-block" src="https://wsrv.nl/?url=https%3A%2F%2Frobotschool.com.co%2Fwp-content%2Fuploads%2F2022%2F07%2FEscuela-de-Robotica-Robot-School-1-1024x576.jpg&w=1200&q=85" alt="Clase"/>
        </div>
      </div>
      <div class="col-lg-6 reveal from-right">
        <div class="about-badge"><i class="bi bi-stars"></i>Sobre Nosotros &middot; Desde 2014</div>
        <h2 class="sec-title">ROBOTSchool: mucho m&aacute;s que rob&oacute;tica</h2>
        <span class="orange-line"></span>
        <p class="mb-3" style="line-height:1.8">Somos un <strong>ecosistema de innovaci&oacute;n educativa</strong> orientado a transformar la ense&ntilde;anza de la tecnolog&iacute;a dentro de las instituciones educativas mediante rob&oacute;tica, programaci&oacute;n, pensamiento computacional, cultura maker y desarrollo de proyectos tecnol&oacute;gicos.</p>
        <p class="mb-4" style="line-height:1.8">Desde nuestras primeras experiencias en <strong>2014</strong>, hemos consolidado un modelo pedag&oacute;gico basado en la experimentaci&oacute;n, la construcci&oacute;n de prototipos y el aprendizaje basado en proyectos. Contamos con academias en <strong>Bogot&aacute; y Cali</strong>, formando a ni&ntilde;os y j&oacute;venes entre 6 y 17 a&ntilde;os.</p>
        <div class="mb-4">
          <div class="checkitem"><div class="ci-dot">&#10003;</div><span>M&aacute;s de <strong>70 laboratorios tecnol&oacute;gicos y Maker Spaces</strong> activos en todo Colombia.</span></div>
          <div class="checkitem"><div class="ci-dot">&#10003;</div><span>Curr&iacute;culo propio articulado con los <strong>17 Objetivos de Desarrollo Sostenible</strong> de la ONU.</span></div>
          <div class="checkitem"><div class="ci-dot">&#10003;</div><span><strong>5 diplomados especializados</strong> para docentes en rob&oacute;tica, STEM, programaci&oacute;n e IA.</span></div>
          <div class="checkitem"><div class="ci-dot">&#10003;</div><span>Plataforma MIEL con <strong>libros digitales, anal&iacute;tica por estudiante</strong> y recursos descargables.</span></div>
        </div>
        <div class="d-flex gap-3 flex-wrap">
          <a href="#academias" class="btn-hero-primary" style="animation:none"><i class="bi bi-grid"></i>Nuestros Programas</a>
          <a href="#contacto" class="btn-card"><i class="bi bi-envelope"></i>Cont&aacute;ctanos</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- PEDAGOGY -->
<div class="ped-strip">
  <div class="container">
    <div class="text-center mb-4 reveal">
      <h2 class="sec-title text-white">Marco Pedag&oacute;gico Internacional</h2>
      <span class="orange-line center"></span>
      <p style="color:rgba(255,255,255,.72);font-size:.97rem">Cuatro marcos conceptuales internacionales integrados en una propuesta coherente y contextualizada</p>
    </div>
    <div class="row g-0 stagger">
      <div class="col-6 col-md-3"><div class="ped-item"><div class="ped-icon"><i class="bi bi-globe-americas"></i></div><h5>ODS</h5><p>Tecnolog&iacute;a con prop&oacute;sito social y ambiental. Proyectos que abordan retos reales del siglo XXI.</p></div></div>
      <div class="col-6 col-md-3"><div class="ped-item"><div class="ped-icon"><i class="bi bi-award"></i></div><h5>ISTE</h5><p>Est&aacute;ndares internacionales. Estudiantes como dise&ntilde;adores innovadores y ciudadanos digitales responsables.</p></div></div>
      <div class="col-6 col-md-3"><div class="ped-item"><div class="ped-icon"><i class="bi bi-kanban"></i></div><h5>ABP</h5><p>Aprendizaje Basado en Proyectos. Robots, IoT, automatizaci&oacute;n y prototipos como herramientas de clase.</p></div></div>
      <div class="col-6 col-md-3"><div class="ped-item" style="padding-bottom:0"><div class="ped-icon"><i class="bi bi-calculator"></i></div><h5>STEM</h5><p>Ciencias, tecnolog&iacute;a, ingenier&iacute;a y matem&aacute;ticas integradas en experiencias interdisciplinarias reales.</p></div></div>
    </div>
  </div>
</div>

<!-- &#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552; ACADEMIAS &mdash; CURSOS DIN&Aacute;MICOS &#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552; -->
<section id="academias" class="bg-gray">
  <div class="container">
    <div class="text-center mb-4 reveal">
      <h2 class="sec-title">Nuestras Academias</h2>
      <span class="orange-line center"></span>
      <p class="sec-sub">Inscripciones abiertas &middot; Grupos reducidos &middot; 3 sedes en Bogot&aacute; y Cali</p>
    </div>

    <!-- Filtros -->
    <div class="text-center mb-4">
      <a href="?#academias" class="filtro-pill <?= !$filtro_sede && !$filtro_edad ? 'on':'' ?>"><i class="bi bi-grid"></i> Todos</a>
      <?php foreach ($sedes_pub as $s): ?>
        <a href="?sede=<?= $s['id'] ?>#academias" class="filtro-pill <?= $filtro_sede==$s['id']?'on':'' ?>">
          <i class="bi bi-geo-alt-fill"></i> <?= h($s['nombre']) ?>
        </a>
      <?php endforeach; ?>
      <a href="?edad=6-9#academias"   class="filtro-pill <?= $filtro_edad=='6-9'?'on':'' ?>">6&ndash;9 a&ntilde;os</a>
      <a href="?edad=10-13#academias" class="filtro-pill <?= $filtro_edad=='10-13'?'on':'' ?>">10&ndash;13 a&ntilde;os</a>
      <a href="?edad=14-17#academias" class="filtro-pill <?= $filtro_edad=='14-17'?'on':'' ?>">14&ndash;17 a&ntilde;os</a>
    </div>

    <?php if (empty($cursos_filtrados)): ?>
      <div style="text-align:center;padding:3rem;color:var(--muted);">
        <i class="bi bi-journal-x" style="font-size:3rem;opacity:.2;display:block;margin-bottom:1rem;"></i>
        <h5>No hay cursos disponibles con estos filtros</h5>
        <a href="?#academias" style="color:var(--orange);font-weight:700;">Ver todos los cursos</a>
      </div>
    <?php else: ?>
      <div class="row g-4 stagger">
        <?php foreach ($cursos_filtrados as $c):
          $grupos_c = $pdo->prepare("
            SELECT g.*,
              (g.cupo_real - COALESCE((SELECT COUNT(*) FROM matriculas m WHERE m.grupo_id=g.id AND m.estado='activa'),0)) AS disponibles
            FROM grupos g WHERE g.curso_id=? AND g.activo=1
            ORDER BY g.dia_semana, g.hora_inicio
          ");
          $grupos_c->execute([$c['id']]);
          $grupos_list = $grupos_c->fetchAll();
          $total_disp  = array_sum(array_column($grupos_list, 'disponibles'));
          $dias_label  = ['lunes'=>'Lun','martes'=>'Mar','miercoles'=>'Mi&eacute;','jueves'=>'Jue','viernes'=>'Vie','sabado'=>'S&aacute;b','domingo'=>'Dom'];
        ?>
        <div class="col-sm-6 col-lg-4">
          <div class="curso-pub-card">
            <?php if ($c['imagen'] && file_exists(ROOT.'/uploads/cursos/'.$c['imagen'])): ?>
              <img src="<?= BASE_URL ?>uploads/cursos/<?= h($c['imagen']) ?>" class="curso-pub-img" alt="<?= h($c['nombre']) ?>"/>
            <?php else: ?>
              <div class="curso-pub-placeholder"><i class="bi bi-robot"></i></div>
            <?php endif; ?>
            <div class="curso-pub-body">
              <div class="mb-2">
                <?php if ($c['edad_min'] && $c['edad_max']): ?>
                  <span class="age-badge"><?= $c['edad_min'] ?>&ndash;<?= $c['edad_max'] ?> a&ntilde;os</span>
                <?php endif; ?>
                <span style="font-size:.68rem;font-weight:800;padding:.22rem .7rem;border-radius:20px;background:rgba(29,169,154,.1);color:#0F6E56;margin-left:.3rem;">
                  <i class="bi bi-geo-alt-fill"></i> <?= h($c['sede_nombre']) ?>
                </span>
              </div>
              <h5 style="font-size:1rem;font-weight:800;color:var(--text);margin-bottom:.5rem;line-height:1.3;"><?= h($c['nombre']) ?></h5>
              <?php if ($c['introduccion']): ?>
                <p style="font-size:.84rem;color:var(--muted);line-height:1.65;margin-bottom:.8rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;"><?= h($c['introduccion']) ?></p>
              <?php endif; ?>
              <?php if ($c['valor'] > 0): ?>
                <div style="font-size:.8rem;font-weight:800;color:var(--blue);margin-bottom:.7rem;">
                  <i class="bi bi-tag-fill me-1"></i><?= formatCOP($c['valor']) ?>
                  <span style="font-weight:400;color:var(--muted);font-size:.72rem;">
                    / <?= $c['tipo_valor']==='semestral' ? 'semestre' : 'mes (4 sesiones)' ?>
                  </span>
                </div>
              <?php endif; ?>
              <!-- Disponibilidad -->
              <div style="display:flex;align-items:center;gap:.4rem;margin-bottom:.6rem;font-size:.78rem;font-weight:700;">
                <span style="width:8px;height:8px;border-radius:50%;background:<?= $total_disp>0?'#16a34a':'var(--orange)' ?>;flex-shrink:0;"></span>
                <span style="color:<?= $total_disp>0?'#16a34a':'var(--orange)' ?>;">
                  <?= $total_disp>0 ? $total_disp.' cupos disponibles' : 'Sin cupos &mdash; Lista de espera' ?>
                </span>
              </div>
              <!-- Horarios -->
              <?php if (!empty($grupos_list)): ?>
                <div style="background:var(--gray);border-radius:10px;padding:.6rem .8rem;margin-bottom:.9rem;">
                  <?php foreach ($grupos_list as $g):
                    $d = (int)$g['disponibles'];
                    $col = $d>3?'#16a34a':($d>0?'#ca8a04':'var(--orange)');
                  ?>
                  <div class="horario-row">
                    <i class="bi bi-clock-fill" style="color:var(--orange);font-size:.75rem;"></i>
                    <span><strong><?= $dias_label[$g['dia_semana']]??$g['dia_semana'] ?></strong> <?= substr($g['hora_inicio'],0,5) ?>&ndash;<?= substr($g['hora_fin'],0,5) ?></span>
                    <?php if ($g['modalidad']!=='presencial'): ?>
                      <span style="font-size:.65rem;background:rgba(29,169,154,.1);color:#0F6E56;padding:.1rem .4rem;border-radius:6px;"><?= ucfirst($g['modalidad']) ?></span>
                    <?php endif; ?>
                    <span style="margin-left:auto;font-size:.7rem;font-weight:800;color:<?= $col ?>;"><?= $d>0?$d.' cupos':'Lleno' ?></span>
                  </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
              <!-- Botones -->
              <div style="display:flex;gap:.5rem;margin-top:auto;">
                <button onclick="verCurso(<?= $c['id'] ?>)"
                        style="flex:1;display:flex;align-items:center;justify-content:center;gap:.4rem;padding:.6rem;background:#fff;color:var(--blue);border:1.5px solid var(--border);border-radius:var(--r-sm);font-weight:700;font-size:.85rem;cursor:pointer;transition:all .2s;"
                        onmouseover="this.style.borderColor='var(--blue)';this.style.background='#f0f4ff';"
                        onmouseout="this.style.borderColor='var(--border)';this.style.background='#fff';">
                  <i class="bi bi-eye-fill"></i> Ver m&aacute;s
                </button>
                <a href="<?= BASE_URL ?>public/registro.php?curso=<?= $c['id'] ?>" class="btn-inscribir" style="flex:1;margin-top:0;">
                  <i class="bi bi-person-plus-fill"></i>
                  <?= $total_disp>0 ? 'Inscribirse' : 'Lista espera' ?>
                </a>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</section>

<!-- PROGRAMAS (est&aacute;ticos originales) -->
<section id="programas">
  <div class="container">
    <div class="text-center mb-5 reveal">
      <h2 class="sec-title">Nuestros Programas</h2>
      <span class="orange-line center"></span>
      <p class="sec-sub">De los 6 a&ntilde;os hasta el mundo laboral. Rutas progresivas de formaci&oacute;n tecnol&oacute;gica para cada etapa.</p>
    </div>
    <div class="row g-4 stagger">
      <div class="col-sm-6 col-lg-4"><div class="prog-card"><div class="prog-card-img-wrap"><img class="prog-card-img" src="https://wsrv.nl/?url=https%3A%2F%2Frobotschool.com.co%2Fwp-content%2Fuploads%2F2022%2F07%2FEscuela-de-Robotica-Robot-School-2-1024x576.jpg&w=1200&q=85" alt="LEGO"/></div><div class="prog-card-body"><span class="age-badge">6&ndash;9 a&ntilde;os</span><h5>Cursos Rob&oacute;tica LEGO</h5><p>Exploraci&oacute;n maker con LEGO, mBot y MakeBlock. Programaci&oacute;n por bloques, primeros prototipos y sensores b&aacute;sicos.</p><a href="#academias" class="btn-card"><i class="bi bi-arrow-right"></i>Inscribirse</a></div></div></div>
      <div class="col-sm-6 col-lg-4"><div class="prog-card"><div class="prog-card-img-wrap"><img class="prog-card-img" src="https://wsrv.nl/?url=https%3A%2F%2Frobotschool.com.co%2Fwp-content%2Fuploads%2F2022%2F07%2FEscuela-de-Robotica-Robot-School-3-1024x576.jpg&w=1200&q=85" alt="Expertos"/></div><div class="prog-card-body"><span class="age-badge">10&ndash;13 a&ntilde;os</span><h5>Expertos en Rob&oacute;tica</h5><p>Arduino, Micro:bit y electr&oacute;nica aplicada. Proyectos interdisciplinarios de automatizaci&oacute;n y sensores inteligentes.</p><a href="#academias" class="btn-card"><i class="bi bi-arrow-right"></i>Ver m&aacute;s</a></div></div></div>
      <div class="col-sm-6 col-lg-4"><div class="prog-card"><div class="prog-card-img-wrap"><img class="prog-card-img" src="https://wsrv.nl/?url=https%3A%2F%2Frobotschool.com.co%2Fwp-content%2Fuploads%2F2022%2F07%2FEscuela-de-Robotica-Robot-School-6-1024x576.jpg&w=1200&q=85" alt="Avanzado"/></div><div class="prog-card-body"><span class="age-badge" style="background:#28a745">14&ndash;17 a&ntilde;os</span><h5>Innovaci&oacute;n Avanzada</h5><p>ESP32, Raspberry Pi, IoT y prototipado digital. Soluciones tecnol&oacute;gicas para problemas del mundo real.</p><a href="#academias" class="btn-card"><i class="bi bi-arrow-right"></i>Ver m&aacute;s</a></div></div></div>
      <div class="col-sm-6 col-lg-4"><div class="prog-card"><div class="prog-card-img-wrap"><img class="prog-card-img" src="https://wsrv.nl/?url=https%3A%2F%2Frobotschool.com.co%2Fwp-content%2Fuploads%2F2022%2F07%2FEscuela-de-Robotica-Robot-School-4-1024x576.jpg&w=1200&q=85" alt="Colegios"/></div><div class="prog-card-body"><span class="age-badge" style="background:var(--blue)">Colegios</span><h5>Clases Extracurriculares</h5><p>Rob&oacute;tica escolar y ambientes MAKER STEAM para colegios. Capacitaci&oacute;n docente y proyectos de innovaci&oacute;n educativa.</p><a href="#colegios" class="btn-card"><i class="bi bi-arrow-right"></i>Ver m&aacute;s</a></div></div></div>
      <div class="col-sm-6 col-lg-4"><div class="prog-card"><div class="prog-card-img-wrap"><img class="prog-card-img" src="https://wsrv.nl/?url=https%3A%2F%2Frobotschool.com.co%2Fwp-content%2Fuploads%2F2022%2F07%2FEscuela-de-Robotica-Robot-School-5-1024x576.jpg&w=1200&q=85" alt="Maker"/></div><div class="prog-card-body"><span class="age-badge" style="background:var(--lblue)">Infraestructura</span><h5>Maker Space STEAM</h5><p>Dise&ntilde;o e implementaci&oacute;n de espacios de innovaci&oacute;n: laboratorios maker, fab labs y centros tecnol&oacute;gicos educativos.</p><a href="#ecosistema" class="btn-card"><i class="bi bi-arrow-right"></i>Ver m&aacute;s</a></div></div></div>
      <div class="col-sm-6 col-lg-4"><div class="prog-card"><div class="prog-card-img-wrap"><img class="prog-card-img" src="https://wsrv.nl/?url=https%3A%2F%2Frobotschool.com.co%2Fwp-content%2Fuploads%2F2022%2F07%2FEscuela-de-Robotica-Robot-School-2-1024x576.jpg&w=1200&q=85" alt="Vacaciones"/></div><div class="prog-card-body"><span class="age-badge" style="background:#6f42c1">Vacaciones</span><h5>Campamentos Tech</h5><p>Campamentos de rob&oacute;tica e innovaci&oacute;n en vacaciones escolares. Aprendizaje intensivo, divertido y basado en proyectos.</p><a href="#contacto" class="btn-card"><i class="bi bi-arrow-right"></i>Ver m&aacute;s</a></div></div></div>
    </div>
  </div>
</section>

<!-- ALIADOS -->
<section id="aliados" class="bg-gray">
  <div class="container">
    <div class="text-center mb-5 reveal">
      <h2 class="sec-title">Colegios & Aliados</h2>
      <span class="orange-line center"></span>
      <p class="sec-sub">Instituciones que ya conf&iacute;an en el ecosistema ROBOTSchool para transformar su educaci&oacute;n tecnol&oacute;gica.</p>
    </div>
    <div class="ally-grid reveal">
      <div class="ally-item"><img src="https://wsrv.nl/?url=https%3A%2F%2Frobotschool.com.co%2Fwp-content%2Fuploads%2F2022%2F07%2FLego-Education-Colombia-Robot-School-1-300x113.png&w=300&q=85" alt="Aliado"/></div>
      <div class="ally-item"><img src="https://wsrv.nl/?url=https%3A%2F%2Frobotschool.com.co%2Fwp-content%2Fuploads%2F2022%2F07%2FLego-Education-Colombia-Robot-School-3-300x113.png&w=300&q=85" alt="Aliado"/></div>
      <div class="ally-item"><img src="https://wsrv.nl/?url=https%3A%2F%2Frobotschool.com.co%2Fwp-content%2Fuploads%2F2022%2F07%2FLego-Education-Colombia-Robot-School-5-300x113.png&w=300&q=85" alt="Aliado"/></div>
      <div class="ally-item"><img src="https://wsrv.nl/?url=https%3A%2F%2Frobotschool.com.co%2Fwp-content%2Fuploads%2F2022%2F07%2FLego-Education-Colombia-Robot-School-26-300x113.png&w=300&q=85" alt="Aliado"/></div>
      <div class="ally-item"><img src="https://wsrv.nl/?url=https%3A%2F%2Frobotschool.com.co%2Fwp-content%2Fuploads%2F2022%2F07%2FLego-Education-Colombia-Robot-School-36-300x113.png&w=300&q=85" alt="Aliado"/></div>
      <div class="ally-item"><img src="https://wsrv.nl/?url=https%3A%2F%2Frobotschool.com.co%2Fwp-content%2Fuploads%2F2022%2F07%2FLego-Education-Colombia-Robot-School-12-300x113.png&w=300&q=85" alt="Aliado"/></div>
      <div class="ally-item"><img src="https://wsrv.nl/?url=https%3A%2F%2Frobotschool.com.co%2Fwp-content%2Fuploads%2F2022%2F07%2FLego-Education-Colombia-Robot-School-11-300x113.png&w=300&q=85" alt="Aliado"/></div>
      <div class="ally-item"><img src="https://wsrv.nl/?url=https%3A%2F%2Frobotschool.com.co%2Fwp-content%2Fuploads%2F2022%2F07%2FLego-Education-Colombia-Robot-School-2-300x113.png&w=300&q=85" alt="Aliado"/></div>
      <div class="ally-item"><img src="https://wsrv.nl/?url=https%3A%2F%2Frobotschool.com.co%2Fwp-content%2Fuploads%2F2022%2F07%2FLego-Education-Colombia-Robot-School-4-300x113.png&w=300&q=85" alt="Aliado"/></div>
      <div class="ally-item"><img src="https://wsrv.nl/?url=https%3A%2F%2Frobotschool.com.co%2Fwp-content%2Fuploads%2F2022%2F07%2FLego-Education-Colombia-Robot-School-10-300x113.png&w=300&q=85" alt="Aliado"/></div>
      <div class="ally-item"><img src="https://wsrv.nl/?url=https%3A%2F%2Frobotschool.com.co%2Fwp-content%2Fuploads%2F2022%2F07%2FLego-Education-Colombia-Robot-School-9-300x113.png&w=300&q=85" alt="Aliado"/></div>
      <div class="ally-item"><img src="https://wsrv.nl/?url=https%3A%2F%2Frobotschool.com.co%2Fwp-content%2Fuploads%2F2022%2F07%2FLego-Education-Colombia-Robot-School-7-300x113.png&w=300&q=85" alt="Aliado"/></div>
    </div>
  </div>
</section>

<!-- TESTIMONIOS -->
<section>
  <div class="container">
    <div class="text-center mb-5 reveal">
      <h2 class="sec-title">Lo que dicen nuestras familias</h2>
      <span class="orange-line center"></span>
    </div>
    <div class="row g-4 justify-content-center stagger">
      <div class="col-md-6 col-lg-4"><div class="test-card"><div class="stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div><blockquote>"La experiencia de mi hijo en ROBOTSchool ha sido transformadora. No solo aprendi&oacute; rob&oacute;tica y programaci&oacute;n, sino que desarroll&oacute; confianza, creatividad y habilidades para resolver problemas."</blockquote><div class="name">Marlen Galindo</div><div class="role">Madre de Tom&aacute;s Lozano &middot; Programa de Rob&oacute;tica</div></div></div>
      <div class="col-md-6 col-lg-4"><div class="test-card"><div class="stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div><blockquote>"Implementar ROBOTSchool en nuestro colegio fue una decisi&oacute;n que transform&oacute; completamente el &aacute;rea de tecnolog&iacute;a. Los estudiantes ahora crean, no solo consumen tecnolog&iacute;a."</blockquote><div class="name">Directora Acad&eacute;mica</div><div class="role">Instituci&oacute;n Educativa &middot; Bogot&aacute;</div></div></div>
      <div class="col-md-6 col-lg-4"><div class="test-card"><div class="stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div><blockquote>"Como docente, el diplomado me cambi&oacute; la perspectiva. Aprend&iacute; a usar la tecnolog&iacute;a como herramienta pedag&oacute;gica y ahora mis estudiantes est&aacute;n completamente motivados."</blockquote><div class="name">Docente de Tecnolog&iacute;a</div><div class="role">Programa de Formaci&oacute;n Docente &middot; Cali</div></div></div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta-section" id="contacto">
  <div class="container" style="position:relative;z-index:1">
    <div class="reveal">
      <span style="display:inline-block;background:rgba(255,255,255,.2);color:#fff;border-radius:20px;padding:.3rem 1rem;font-size:.8rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;margin-bottom:1rem">&iquest;Listo para inscribir a tu hijo?</span>
      <h2>&iquest;Tu familia est&aacute; lista para el futuro tecnol&oacute;gico?</h2>
      <p>&Uacute;nete a m&aacute;s de 500 estudiantes que est&aacute;n aprendiendo rob&oacute;tica, programaci&oacute;n y STEAM con ROBOTSchool.</p>
      <div>
        <a href="<?= BASE_URL ?>public/registro.php" class="btn-cta-white"><i class="bi bi-person-plus-fill"></i>Inscribirse ahora</a>
        <a href="https://wa.link/ktfv4o" target="_blank" class="btn-cta-outline"><i class="bi bi-whatsapp"></i>WhatsApp</a>
        <a href="tel:+573186541859" class="btn-cta-outline"><i class="bi bi-telephone-fill"></i>(57) 318 654 1859</a>
      </div>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <div class="container">
    <div class="row gy-4">
      <div class="col-lg-4">
        <div class="footer-logo"><img src="https://wsrv.nl/?url=https%3A%2F%2Frobotschool.com.co%2Fwp-content%2Fuploads%2F2022%2F07%2F02-RobotSchool-Escuela-de-Robotica-1.png&w=300&q=90" alt="ROBOTSchool"/></div>
        <p class="small mb-3" style="line-height:1.75">Somos un ecosistema de innovaci&oacute;n educativa que busca incentivar mentes creativas en ni&ntilde;os, j&oacute;venes y adultos, liderando una generaci&oacute;n de creadores que construyan soluciones tecnol&oacute;gicas para su entorno.</p>
        <div class="mb-3">
          <a href="https://www.facebook.com/RobotSchoolEdu/" class="social-btn" target="_blank"><i class="bi bi-facebook"></i></a>
          <a href="https://www.instagram.com/robotschoolco/" class="social-btn" target="_blank"><i class="bi bi-instagram"></i></a>
          <a href="https://www.youtube.com/c/ROBOTSchoolco" class="social-btn" target="_blank"><i class="bi bi-youtube"></i></a>
          <a href="https://wa.link/ktfv4o" class="social-btn" target="_blank"><i class="bi bi-whatsapp"></i></a>
        </div>
      </div>
      <div class="col-6 col-lg-2">
        <div class="footer-title">Programas</div>
        <ul class="footer-links">
          <li><a href="#">Rob&oacute;tica LEGO</a></li>
          <li><a href="#">Expertos Rob&oacute;tica</a></li>
          <li><a href="#">Cursos Libres</a></li>
          <li><a href="#">ROBOTSchool TECH</a></li>
          <li><a href="#">Vacaciones</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <div class="footer-title">Instituciones</div>
        <ul class="footer-links">
          <li><a href="#">Para Colegios</a></li>
          <li><a href="#">Maker Spaces</a></li>
          <li><a href="#">Laboratorios</a></li>
          <li><a href="#">Acompa&ntilde;amiento</a></li>
          <li><a href="#">Alianzas</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <div class="footer-title">Docentes</div>
        <ul class="footer-links">
          <li><a href="#">Rob&oacute;tica Educativa</a></li>
          <li><a href="#">Programaci&oacute;n</a></li>
          <li><a href="#">Educaci&oacute;n STEM</a></li>
          <li><a href="#">IA en Educaci&oacute;n</a></li>
          <li><a href="#">Innovaci&oacute;n</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <div class="footer-title">Contacto</div>
        <div class="contact-info"><i class="bi bi-geo-alt-fill"></i><span>Calle 75 #20b-62, Bogot&aacute;</span></div>
        <div class="contact-info"><i class="bi bi-telephone-fill"></i><span>318 654 1859</span></div>
        <div class="contact-info"><i class="bi bi-envelope-fill"></i><span>info@robotschool.com.co</span></div>
        <div class="contact-info"><i class="bi bi-globe"></i><span>robotschool.com.co</span></div>
        <div class="mt-3">
          <a href="<?= BASE_URL ?>login.php" style="font-size:.78rem;color:rgba(255,255,255,.35);text-decoration:none;">
            <i class="bi bi-lock-fill me-1"></i>Acceso administradores
          </a>
        </div>
      </div>
    </div>
    <hr class="footer-divider my-4"/>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
      <p class="small mb-0">&#169; <?= date('Y') ?> ROBOTSchool Academy Learning &middot; Escuelas STEAM Colombia SAS.</p>
      <p class="small mb-0" style="opacity:.4">Hecho con &#10084;&#65039; en Colombia &middot; Desde 2014</p>
    </div>
  </div>
</footer>

<!-- &#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552; MODAL DETALLE DEL CURSO &#9552;&#9552;&#9552;&#9552;&#9552;&#9552;&#9552; -->
<div id="modalCurso" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,22,35,.75);backdrop-filter:blur(6px);overflow-y:auto;padding:1.5rem 1rem;">
  <div style="max-width:900px;margin:0 auto;background:#fff;border-radius:20px;overflow:hidden;position:relative;box-shadow:0 24px 80px rgba(0,0,0,.35);">

    <!-- Bot&oacute;n cerrar -->
    <button onclick="cerrarModal()" style="position:absolute;top:1rem;right:1rem;z-index:10;width:38px;height:38px;background:rgba(0,0,0,.5);color:#fff;border:none;border-radius:50%;font-size:1.1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .2s;"
            onmouseover="this.style.background='var(--orange)'" onmouseout="this.style.background='rgba(0,0,0,.5)'">
      <i class="bi bi-x-lg"></i>
    </button>

    <!-- Spinner de carga -->
    <div id="modalLoading" style="padding:4rem;text-align:center;color:var(--muted);">
      <div style="width:40px;height:40px;border:3px solid var(--border);border-top-color:var(--orange);border-radius:50%;animation:spin .7s linear infinite;margin:0 auto 1rem;"></div>
      Cargando informaci&oacute;n del curso...
    </div>

    <!-- Contenido del modal -->
    <div id="modalContenido" style="display:none;">

      <!-- Galer&iacute;a de im&aacute;genes -->
      <div id="galeriaWrap" style="position:relative;background:var(--dark);height:320px;overflow:hidden;">
        <div id="galeriaSlides" style="display:flex;height:100%;transition:transform .4s ease;"></div>
        <button id="galPrev" onclick="galeria(-1)" style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);width:40px;height:40px;background:rgba(255,255,255,.2);border:none;border-radius:50%;color:#fff;font-size:1rem;cursor:pointer;display:none;">&#8249;</button>
        <button id="galNext" onclick="galeria(1)"  style="position:absolute;right:1rem;top:50%;transform:translateY(-50%);width:40px;height:40px;background:rgba(255,255,255,.2);border:none;border-radius:50%;color:#fff;font-size:1rem;cursor:pointer;display:none;">&#8250;</button>
        <div id="galDots" style="position:absolute;bottom:.8rem;left:50%;transform:translateX(-50%);display:flex;gap:.4rem;"></div>
      </div>

      <!-- Cuerpo del modal -->
      <div style="padding:1.8rem 2rem 2rem;">

        <!-- Cabecera -->
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1.2rem;flex-wrap:wrap;">
          <div style="flex:1;">
            <div id="mBadges" style="display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:.6rem;"></div>
            <h2 id="mNombre" style="font-family:'Poppins',sans-serif;font-size:1.5rem;font-weight:800;color:var(--text);margin:0;line-height:1.3;"></h2>
          </div>
          <div id="mValor" style="text-align:right;flex-shrink:0;"></div>
        </div>

        <!-- Grid de info -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">

          <!-- COLUMNA IZQUIERDA -->
          <div>
            <!-- Introducci&oacute;n -->
            <div id="mIntroWrap">
              <h5 style="font-size:.82rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:.6rem;">
                <i class="bi bi-info-circle-fill" style="color:var(--orange);"></i> Sobre el curso
              </h5>
              <p id="mIntro" style="font-size:.9rem;color:var(--text);line-height:1.75;margin-bottom:.5rem;"></p>
              <p id="mObjetivos" style="font-size:.85rem;color:var(--muted);line-height:1.7;"></p>
            </div>

            <!-- M&oacute;dulos -->
            <div id="mModsWrap" style="margin-top:1.2rem;display:none;">
              <h5 style="font-size:.82rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:.6rem;">
                <i class="bi bi-collection-fill" style="color:var(--orange);"></i> Temario
              </h5>
              <div id="mMods"></div>
            </div>

            <!-- Materiales -->
            <div id="mMatsWrap" style="margin-top:1.2rem;display:none;">
              <h5 style="font-size:.82rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:.6rem;">
                <i class="bi bi-box-seam-fill" style="color:var(--orange);"></i> Materiales incluidos
              </h5>
              <div id="mMats"></div>
            </div>
          </div>

          <!-- COLUMNA DERECHA -->
          <div>
            <!-- Horarios -->
            <div id="mHorariosWrap">
              <h5 style="font-size:.82rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:.6rem;">
                <i class="bi bi-calendar3" style="color:var(--orange);"></i> Horarios disponibles
              </h5>
              <div id="mHorarios"></div>
            </div>

            <!-- Info adicional -->
            <div id="mInfoExtra" style="margin-top:1.2rem;background:var(--gray);border-radius:12px;padding:1rem;"></div>

            <!-- Botones CTA -->
            <div style="margin-top:1.5rem;display:flex;flex-direction:column;gap:.7rem;">
              <a id="mBtnInscribir" href="#" style="display:flex;align-items:center;justify-content:center;gap:.5rem;padding:.85rem;background:var(--orange);color:#fff;border-radius:12px;font-weight:800;font-size:.95rem;text-decoration:none;transition:all .25s;box-shadow:0 6px 20px rgba(242,101,34,.35);"
                 onmouseover="this.style.background='var(--orange-d)';this.style.transform='translateY(-2px)'"
                 onmouseout="this.style.background='var(--orange)';this.style.transform=''">
                <i class="bi bi-person-plus-fill"></i> Inscribir a mi hijo/hija
              </a>
              <a href="https://wa.link/ktfv4o" target="_blank"
                 style="display:flex;align-items:center;justify-content:center;gap:.5rem;padding:.75rem;background:#25D366;color:#fff;border-radius:12px;font-weight:700;font-size:.88rem;text-decoration:none;transition:all .25s;"
                 onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                <i class="bi bi-whatsapp"></i> Consultar por WhatsApp
              </a>
            </div>
          </div>
        </div>
      </div>
    </div><!-- /modalContenido -->
  </div>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
#modalCurso { animation: fadeInModal .2s ease; }
@keyframes fadeInModal { from { opacity:0; } to { opacity:1; } }
@media(max-width:600px) {
  #modalContenido > div > div[style*="grid-template-columns"] {
    grid-template-columns: 1fr !important;
  }
  #galeriaWrap { height: 220px !important; }
}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script>
// &#9472;&#9472; SCROLL REVEAL &#9472;&#9472;
const revealObs = new IntersectionObserver(entries => {
  entries.forEach(e => { if(e.isIntersecting){e.target.classList.add('visible');revealObs.unobserve(e.target);} });
},{threshold:.12,rootMargin:'0px 0px -40px 0px'});
document.querySelectorAll('.reveal,.stagger').forEach(el=>revealObs.observe(el));

// &#9472;&#9472; NAVBAR SCROLL &#9472;&#9472;
const nav = document.getElementById('mainNav');
window.addEventListener('scroll',()=>{
  nav.classList.toggle('scrolled',window.scrollY>60);
  document.getElementById('backTop').classList.toggle('show',window.scrollY>400);
});

document.getElementById('heroSlider').addEventListener('slide.bs.carousel',function(e){
  document.querySelectorAll('.slide-bg').forEach((img,i)=>{
    img.style.transform = i===e.to ? 'scale(1.05)' : 'scale(1)';
  });
});

// &#9472;&#9472; MODAL DETALLE CURSO &#9472;&#9472;
let galIdx = 0;
let galTotal = 0;

function verCurso(id) {
  const modal = document.getElementById('modalCurso');
  document.getElementById('modalLoading').style.display = 'block';
  document.getElementById('modalContenido').style.display = 'none';
  modal.style.display = 'block';
  document.body.style.overflow = 'hidden';

  fetch('curso_detalle.php?id=' + id)
    .then(r => r.json())
    .then(c => {
      if (c.error) { cerrarModal(); alert(c.error); return; }
      renderModal(c);
    })
    .catch(() => { cerrarModal(); alert('Error al cargar el curso.'); });
}

function renderModal(c) {
  const dias = {lunes:'Lunes',martes:'Martes',miercoles:'Mi&eacute;rcoles',jueves:'Jueves',viernes:'Viernes',sabado:'S&aacute;bado',domingo:'Domingo'};

  // GALER&Iacute;A
  const slides = document.getElementById('galeriaSlides');
  slides.innerHTML = '';
  galIdx = 0;
  const imgs = [];

  // Imagen principal primero
  if (c.imagen_url) imgs.push({ url: c.imagen_url, caption: c.nombre });
  // Galer&iacute;a adicional
  if (c.galeria && c.galeria.length) c.galeria.forEach(g => imgs.push({ url: g.url, caption: g.caption || '' }));

  galTotal = imgs.length;
  imgs.forEach(img => {
    const div = document.createElement('div');
    div.style.cssText = 'flex-shrink:0;width:100%;height:320px;';
    div.innerHTML = `<img src="${img.url}" style="width:100%;height:100%;object-fit:cover;" alt="${img.caption || ''}"/>`;
    slides.appendChild(div);
  });

  // Dots
  const dots = document.getElementById('galDots');
  dots.innerHTML = '';
  if (galTotal > 1) {
    imgs.forEach((_, i) => {
      const d = document.createElement('span');
      d.style.cssText = `width:8px;height:8px;border-radius:50%;background:${i===0?'#fff':'rgba(255,255,255,.4)'};cursor:pointer;transition:background .2s;`;
      d.onclick = () => irSlide(i);
      dots.appendChild(d);
    });
    document.getElementById('galPrev').style.display = 'flex';
    document.getElementById('galNext').style.display = 'flex';
  } else {
    document.getElementById('galPrev').style.display = 'none';
    document.getElementById('galNext').style.display = 'none';
  }

  // Placeholder si no hay im&aacute;genes
  if (galTotal === 0) {
    slides.innerHTML = `<div style="flex-shrink:0;width:100%;height:320px;background:linear-gradient(135deg,#0f1623,#1E4DA1);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.15);font-size:5rem;"><i class="bi bi-robot"></i></div>`;
  }

  // BADGES
  const badges = document.getElementById('mBadges');
  badges.innerHTML = '';
  if (c.edad_min && c.edad_max) {
    badges.innerHTML += `<span style="background:var(--orange);color:#fff;font-size:.68rem;font-weight:800;padding:.22rem .7rem;border-radius:20px;">${c.edad_min}&ndash;${c.edad_max} a&ntilde;os</span>`;
  }
  badges.innerHTML += `<span style="background:rgba(29,169,154,.1);color:#0F6E56;font-size:.68rem;font-weight:800;padding:.22rem .7rem;border-radius:20px;"><i class="bi bi-geo-alt-fill"></i> ${c.sede_nombre}</span>`;

  // NOMBRE
  document.getElementById('mNombre').textContent = c.nombre;

  // VALOR
  const tipoLabel = c.tipo_valor === 'semestral' ? 'semestre' : 'mes (4 sesiones)';
  document.getElementById('mValor').innerHTML = c.valor > 0
    ? `<div style="font-size:1.4rem;font-weight:900;color:var(--orange);font-family:'Poppins',sans-serif;">${c.valor_fmt}</div><div style="font-size:.72rem;color:var(--muted);">por ${tipoLabel}</div>`
    : `<span style="background:#dcfce7;color:#15803d;font-size:.8rem;font-weight:800;padding:.3rem .8rem;border-radius:20px;">Gratuito</span>`;

  // INTRO Y OBJETIVOS
  document.getElementById('mIntro').textContent = c.introduccion || '';
  document.getElementById('mObjetivos').textContent = c.objetivos ? '&#127919; ' + c.objetivos : '';
  document.getElementById('mIntroWrap').style.display = (c.introduccion || c.objetivos) ? 'block' : 'none';

  // M&Oacute;DULOS
  const modsEl = document.getElementById('mMods');
  modsEl.innerHTML = '';
  if (c.modulos && c.modulos.length) {
    c.modulos.forEach((m, i) => {
      modsEl.innerHTML += `
        <div style="display:flex;gap:.7rem;padding:.6rem 0;border-bottom:1px solid var(--border);">
          <div style="width:24px;height:24px;background:var(--orange);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:800;flex-shrink:0;margin-top:2px;">${i+1}</div>
          <div>
            <div style="font-size:.85rem;font-weight:700;color:var(--text);">${m.nombre}</div>
            ${m.descripcion ? `<div style="font-size:.78rem;color:var(--muted);margin-top:.2rem;">${m.descripcion}</div>` : ''}
          </div>
        </div>`;
    });
    document.getElementById('mModsWrap').style.display = 'block';
  }

  // MATERIALES
  const matsEl = document.getElementById('mMats');
  matsEl.innerHTML = '';
  if (c.materiales && c.materiales.length) {
    c.materiales.forEach(m => {
      matsEl.innerHTML += `
        <div style="display:flex;align-items:center;gap:.6rem;padding:.4rem 0;border-bottom:1px solid var(--border);">
          <i class="bi bi-box-fill" style="color:var(--orange);font-size:.85rem;flex-shrink:0;"></i>
          <span style="font-size:.85rem;color:var(--text);flex:1;">${m.nombre}</span>
          <span style="font-size:.75rem;font-weight:700;color:var(--muted);">x${m.cantidad}</span>
          ${m.kit_referencia ? `<span style="font-size:.65rem;background:#e8f0fe;color:#1E4DA1;padding:.15rem .5rem;border-radius:8px;">${m.kit_referencia}</span>` : ''}
        </div>`;
    });
    document.getElementById('mMatsWrap').style.display = 'block';
  }

  // HORARIOS
  const horEl = document.getElementById('mHorarios');
  horEl.innerHTML = '';
  if (c.grupos && c.grupos.length) {
    c.grupos.forEach(g => {
      const disp = parseInt(g.disponibles);
      const col  = disp > 3 ? '#16a34a' : (disp > 0 ? '#ca8a04' : 'var(--orange)');
      const txt  = disp > 0 ? disp + ' cupos' : 'Lleno';
      const mod  = g.modalidad !== 'presencial' ? `<span style="font-size:.65rem;background:rgba(29,169,154,.1);color:#0F6E56;padding:.1rem .4rem;border-radius:6px;margin-left:.3rem;">${g.modalidad}</span>` : '';
      horEl.innerHTML += `
        <div style="display:flex;align-items:center;gap:.6rem;padding:.55rem .8rem;background:var(--gray);border-radius:10px;margin-bottom:.4rem;">
          <i class="bi bi-clock-fill" style="color:var(--orange);font-size:.85rem;flex-shrink:0;"></i>
          <div style="flex:1;">
            <strong style="font-size:.85rem;">${dias[g.dia_semana]||g.dia_semana}</strong>
            <span style="font-size:.82rem;color:var(--muted);margin-left:.4rem;">${g.hora_inicio.substring(0,5)} &ndash; ${g.hora_fin.substring(0,5)}</span>
            ${mod}
          </div>
          <span style="font-size:.72rem;font-weight:800;color:${col};">${txt}</span>
        </div>`;
    });
  } else {
    horEl.innerHTML = '<p style="font-size:.85rem;color:var(--muted);">Pr&oacute;ximamente se publicar&aacute;n los horarios.</p>';
  }

  // INFO EXTRA
  document.getElementById('mInfoExtra').innerHTML = `
    <div style="display:flex;gap:1rem;flex-wrap:wrap;">
      ${c.edad_min ? `<div style="text-align:center;"><div style="font-size:1.1rem;font-weight:900;color:var(--orange);font-family:'Poppins',sans-serif;">${c.edad_min}&ndash;${c.edad_max}</div><div style="font-size:.7rem;color:var(--muted);font-weight:600;">A&ntilde;os</div></div>` : ''}
      ${c.cupo_maximo ? `<div style="text-align:center;"><div style="font-size:1.1rem;font-weight:900;color:var(--blue);font-family:'Poppins',sans-serif;">${c.cupo_maximo}</div><div style="font-size:.7rem;color:var(--muted);font-weight:600;">Cupo m&aacute;x.</div></div>` : ''}
      <div style="text-align:center;"><div style="font-size:1.1rem;font-weight:900;color:#16a34a;font-family:'Poppins',sans-serif;">${(c.grupos||[]).length}</div><div style="font-size:.7rem;color:var(--muted);font-weight:600;">Grupos</div></div>
    </div>`;

  // BTN INSCRIBIR
  const btnInscribir = document.getElementById('mBtnInscribir');
  const totalDisp = (c.grupos||[]).reduce((s,g) => s + parseInt(g.disponibles||0), 0);
  btnInscribir.href = '<?= BASE_URL ?>portal/login.php?curso=' + c.id;
  btnInscribir.innerHTML = totalDisp > 0
    ? '<i class="bi bi-person-plus-fill"></i> Inscribir a mi hijo/hija'
    : '<i class="bi bi-bell-fill"></i> Unirse a lista de espera';
  btnInscribir.style.background = totalDisp > 0 ? 'var(--orange)' : 'var(--muted)';

  // Mostrar
  document.getElementById('modalLoading').style.display = 'none';
  document.getElementById('modalContenido').style.display = 'block';
}

function cerrarModal() {
  document.getElementById('modalCurso').style.display = 'none';
  document.body.style.overflow = '';
}

function galeria(dir) {
  galIdx = (galIdx + dir + galTotal) % galTotal;
  irSlide(galIdx);
}

function irSlide(i) {
  galIdx = i;
  document.getElementById('galeriaSlides').style.transform = `translateX(-${i*100}%)`;
  document.querySelectorAll('#galDots span').forEach((d, j) => {
    d.style.background = j === i ? '#fff' : 'rgba(255,255,255,.4)';
  });
}

// Cerrar con clic fuera del modal o ESC
document.getElementById('modalCurso').addEventListener('click', function(e) {
  if (e.target === this) cerrarModal();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarModal(); });
</script>
</body>
</html>
