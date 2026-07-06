const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

// If this folder is a junction, chdir to the real path so Expo's public-folder
// guard does not treat bundled assets as outside the project (HTTP 500 in Expo Go).
const projectRoot = fs.realpathSync(process.cwd());
process.chdir(projectRoot);

// PHP backend uses `public/`; Expo must use a separate in-project static folder.
const expoStatic = path.join(projectRoot, 'expo-static');
if (!fs.existsSync(expoStatic)) {
  fs.mkdirSync(expoStatic, { recursive: true });
}
process.env.EXPO_PUBLIC_FOLDER = 'expo-static';

const expoPath = path.join(
  projectRoot,
  'node_modules',
  '.bin',
  process.platform === 'win32' ? 'expo.cmd' : 'expo'
);

// Forward any extra CLI args (e.g. `npm start -- -c` to clear the Metro cache).
const extraArgs = process.argv.slice(2);
const expoArgs = ['start', ...extraArgs];

const child = process.platform === 'win32'
  ? spawn('cmd.exe', ['/c', expoPath, ...expoArgs], {
      cwd: projectRoot,
      stdio: 'inherit',
    })
  : spawn(expoPath, expoArgs, {
      cwd: projectRoot,
      stdio: 'inherit',
    });

child.on('exit', (code) => {
  process.exit(code ?? 0);
});

child.on('error', (error) => {
  console.error(error);
  process.exit(1);
});