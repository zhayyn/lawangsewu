import re

with open('/var/www/lawangsewu/widgets/views/php/public/tvmedia.php', 'r') as f:
    content = f.read()

# CSS Replacements
css = content
css = css.replace('--bg:#040b14;', '--bg:#f8fafc;')
css = css.replace('--bar:#0a1628;', '--bar:#ffffff;')
css = css.replace('color:#fff;', 'color:#1e293b;')
css = css.replace('background:#000;', 'background:#f1f5f9;')
css = css.replace('color:#64748b;', 'color:#475569;')
css = css.replace('background:rgba(4,11,20,.82);', 'background:rgba(255,255,255,.9);')
css = css.replace('border:1px solid rgba(255,255,255,.1);', 'border:1px solid rgba(0,0,0,.1);')
css = css.replace('color:rgba(255,255,255,.65);', 'color:rgba(0,0,0,.75);')
css = css.replace('background:rgba(255,255,255,.08);', 'background:rgba(0,0,0,.08);')
css = css.replace('border-top:1px solid rgba(255,255,255,.07);', 'border-top:1px solid rgba(0,0,0,.07);')
css = css.replace('color:#e2e8f0;', 'color:#0f172a;')
css = css.replace('background:rgba(255,255,255,.09);', 'background:rgba(0,0,0,.09);')
css = css.replace('background:rgba(255,255,255,.18);', 'background:rgba(0,0,0,.15);')
css = css.replace('background:rgba(255,255,255,.4)', 'background:rgba(0,0,0,.3)')
css = css.replace('background:rgba(255,255,255,.07);', 'background:rgba(0,0,0,.05);')
css = css.replace('color:#94a3b8;', 'color:#475569;')
css = css.replace('background:rgba(255,255,255,.14);', 'background:rgba(0,0,0,.1);')
css = css.replace('color:#f1f5f9', 'color:#0f172a')
css = css.replace('background:rgba(4,11,20,.97);', 'background:rgba(255,255,255,.97);')
css = css.replace('background:rgba(255,255,255,.04);', 'background:rgba(0,0,0,.03);')
css = css.replace('background:rgba(255,255,255,.06);', 'background:rgba(0,0,0,.04);')
css = css.replace('border:1px solid rgba(255,255,255,.09);', 'border:1px solid rgba(0,0,0,.1);')
css = css.replace('background:#0f172a', 'background:#ffffff')
css = css.replace('color:rgba(255,255,255,.15);', 'color:rgba(0,0,0,.3);')

# Data replacements
css = css.replace("var SK = 'tvmedia_playlist_v11';", "var SK = 'tvmedia_playlist_v12';")
css = css.replace("""    {id:'daftar-pegawai', type:'widget', label:'Profil Pegawai & Statistik SIKEP',
     src:'/daftar-pegawai', duration:20000, fallback:null},""",
"""    {id:'statistik-sikep', type:'iframe', label:'Statistik SIKEP',
     src:'https://sikep.mahkamahagung.go.id/informasi/statistik', duration:20000, fallback:'Silakan login SIKEP di browser TV ini jika halaman tidak muncul.'},""")

with open('/var/www/lawangsewu/widgets/views/php/public/tvmedia.php', 'w') as f:
    f.write(css)

