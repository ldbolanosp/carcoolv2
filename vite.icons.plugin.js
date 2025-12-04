import fs from 'fs/promises';
import path from 'path';
import { getIconsCSS } from '@iconify/utils';

/**
 * Vite plugin para generar iconos CSS de Iconify.
 *
 * OPTIMIZACIÓN PARA LARAVEL CLOUD:
 * - Si iconify.css ya existe (commitado en git), se salta la regeneración
 * - Si las fuentes de fontawesome/flags ya existen, se salta la copia
 * - Esto ahorra ~10-20 segundos en cada deploy
 * - Para regenerar manualmente: elimina los archivos y corre `yarn build`
 */
export default function iconifyPlugin() {
  return {
    name: 'vite-iconify-plugin',
    apply: 'build', // Run only during build

    async buildStart() {
      const outputPath = path.resolve(process.cwd(), 'resources/assets/vendor/fonts/iconify/iconify.css');

      // Check if iconify.css already exists
      let iconifyExists = false;
      try {
        await fs.access(outputPath);
        iconifyExists = true;
        console.log('✅ Iconify CSS already exists, skipping generation');
      } catch {
        // File doesn't exist, proceed with generation
      }

      if (!iconifyExists) {
        console.log('🔨 Generating iconify CSS file...');

        try {
          const iconSetPaths = [path.resolve(process.cwd(), 'node_modules/@iconify-json/tabler/icons.json')];

          const iconSets = await Promise.all(
            iconSetPaths.map(async filePath => {
              const data = await fs.readFile(filePath, 'utf-8');
              return JSON.parse(data);
            })
          );

          const allIcons = iconSets
            .map(iconSet => {
              return getIconsCSS(iconSet, Object.keys(iconSet.icons), {
                iconSelector: '.{prefix}-{name}',
                commonSelector: '.ti',
                format: 'expanded'
              });
            })
            .join('\n');

          const dir = path.dirname(outputPath);
          await fs.mkdir(dir, { recursive: true });
          await fs.writeFile(outputPath, allIcons, 'utf8');

          console.log(`✅ Iconify CSS generated at: ${outputPath}`);
        } catch (error) {
          console.error('❌ Error generating Iconify CSS:', error);
        }
      }

      // Copy additional font files (fontawesome, flags)
      const additionalFiles = [
        {
          name: 'fontawesome',
          filesPath: path.resolve(process.cwd(), 'node_modules/@fortawesome/fontawesome-free/webfonts'),
          destPath: path.resolve(process.cwd(), 'resources/assets/vendor/fonts/fontawesome'),
          checkFile: 'fa-solid-900.woff2'
        },
        {
          name: 'flags',
          filesPath: path.resolve(process.cwd(), 'node_modules/flag-icons/flags'),
          destPath: path.resolve(process.cwd(), 'resources/assets/vendor/fonts/flags'),
          checkFile: '1x1'
        }
      ];

      for (const file of additionalFiles) {
        // Check if files already exist
        try {
          await fs.access(path.join(file.destPath, file.checkFile));
          console.log(`✅ ${file.name} fonts already exist, skipping copy`);
          continue;
        } catch {
          // Files don't exist, proceed with copy
        }

        console.log(`📦 Copying ${file.name} fonts...`);

        try {
          await fs.mkdir(file.destPath, { recursive: true });
          const items = await fs.readdir(file.filesPath, { withFileTypes: true });
          for (const item of items) {
            const srcPath = path.join(file.filesPath, item.name);
            const destPath = path.join(file.destPath, item.name);
            if (item.isDirectory()) {
              await fs.mkdir(destPath, { recursive: true });
              const subItems = await fs.readdir(srcPath);
              for (const subItem of subItems) {
                await fs.copyFile(path.join(srcPath, subItem), path.join(destPath, subItem));
              }
            } else {
              await fs.copyFile(srcPath, destPath);
            }
          }
          console.log(`✅ ${file.name} fonts copied`);
        } catch (error) {
          console.error(`❌ Error copying ${file.name} fonts:`, error);
        }
      }
    }
  };
}
