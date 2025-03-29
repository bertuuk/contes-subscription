const fs = require('fs');
const path = require('path');
const iconsFolder = path.resolve(__dirname, '../assets/icons');
const outputFile = path.resolve(__dirname, '../assets/sass/_icons.scss')

// Leer todos los archivos SVG de la carpeta
const icons = fs.readdirSync(iconsFolder).filter(file => file.endsWith('.svg'));

// Generar las clases CSS
let cssContent = `/* Clases generadas automáticamente para los iconos */\n\n.dahlia-icon {
  display: inline-block;
  width: 2rem;
  height: 2rem;
  background-repeat: no-repeat;
  background-size: contain;
  background-position: center;
  -webkit-mask-size: contain;
  -webkit-mask-repeat: no-repeat;
  -webkit-mask-position: center;
  mask-size: contain;
  mask-repeat: no-repeat;
  mask-position: center;
  background: black;
}\n\n`;

icons.forEach(icon => {
  const iconName = path.basename(icon, '.svg'); // Nombre del icono sin la extensión
  cssContent += `.dahlia-${iconName} {
  -webkit-mask-image: url('../icons/${icon}');
  mask-image: url('../icons/${icon}');
}\n\n`;
});

// Guardar las clases generadas en el archivo SCSS
fs.writeFileSync(outputFile, cssContent, 'utf8');
