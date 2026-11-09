<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
*{box-sizing:border-box;}
body{
  margin:0;
  font-family:"Prompt",sans-serif;
  background: linear-gradient(180deg,#fff6fb 0%, #fff 100%);
  color:#5b4a47;
}

/* ===== Top Banner ===== */
.top-banner{
  background: linear-gradient(90deg, #ff5f88, #ffb7ce);
  color:#ffffff;
  text-align:center;
  padding: 12px;
  font-weight: 900;
  position: relative;
  overflow:hidden;
}
.top-banner::after{
  content:"";
  position:absolute;
  inset:-80px;
  background: radial-gradient(circle at 30% 40%, rgba(255,255,255,0.35) 0%, transparent 55%);
  transform: rotate(12deg);
}

/* ===== Navbar ===== */
header{
  background-color: rgba(255,255,255,0.92);
  padding: 16px 40px;
  display:flex;
  justify-content:space-between;
  align-items:center;
  box-shadow:0 10px 25px rgba(0,0,0,0.06);
  backdrop-filter: blur(10px);
  position: sticky;
  top:0;
  z-index:1000;
}

.brand{
  display:flex;
  align-items:center;
  gap:10px;
}

.brand .logo{
  width:42px;height:42px;border-radius:14px;
  background: linear-gradient(135deg,#ff5f88,#ffb7ce);
  box-shadow: 0 14px 35px rgba(255,95,136,0.25);
}

header h1{
  margin:0;
  font-size:20px;
  color:#ff5f88;
  font-weight:900;
  line-height:1.1;
}

header small{
  display:block;
  font-size:12px;
  color:#8a6f6a;
  font-weight:800;
  margin-top:2px;
}

nav{
  display:flex;
  align-items:center;
  gap:6px;
}

nav a{
  color:#5b4a47;
  text-decoration:none;
  font-weight:900;
  padding:10px 14px;
  border-radius:14px;
  transition:0.2s;
}

nav a:hover{
  background:#ffe1ea;
  color:#ff5f88;
}

nav a.active{
  background:#ffe1ea;
  color:#ff5f88;
}

/* =========================
   ✅ STANDARD MOBILE NAV
========================= */

.menu-btn{
  display:none;
  border:none;
  cursor:pointer;
  font-size: 22px;
  font-weight: 900;
  width: 48px;
  height: 48px;
  border-radius: 16px;
  background: #ffe1ea;
  color:#ff5f88;
  box-shadow: 0 14px 30px rgba(0,0,0,0.08);
  transition: 0.2s;
}

.menu-btn:hover{
  transform: translateY(-2px);
}

/* Desktop */
@media (min-width: 901px){
  nav{
    display:flex !important;
    flex-direction: row;
    gap: 6px;
    position: static;
    background: transparent;
    box-shadow: none;
    padding: 0;
  }
  .menu-btn{
    display:none !important;
  }
}

/* Mobile */
@media (max-width: 900px){

  header{
    padding: 14px 16px;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
  }

  .brand{
    flex:1;
  }

  .menu-btn{
    display:flex !important;
    align-items:center;
    justify-content:center;
    position: static;
  }

  nav{
    display:none !important;
    position:absolute;
    top: 100%;
    left: 16px;
    right: 16px;
    background:#fff;
    border-radius:18px;
    padding:12px;
    box-shadow:0 18px 50px rgba(0,0,0,0.12);
    flex-direction: column;
    gap:10px;
    z-index:9999;
  }

  nav.show{
    display:flex !important;
  }

  nav a{
    width:100%;
    text-align:center;
    padding: 14px;
    border-radius:14px;
    font-size:14px;
  }
}
</style>
