const { spawnSync } = require('node:child_process');
const path = require('node:path');

const repoRoot = path.resolve(__dirname, '..');
const wpEnvBin = path.join(repoRoot, 'node_modules', '.bin', 'wp-env');
const configArg = '--config=.wp-env.test.json';
const pluginSlug = 'podlove-podcasting-plugin-for-wordpress';

function runWpEnv(args) {
  return spawnSync(wpEnvBin, [configArg, 'run', 'cli', 'wp', ...args], {
    cwd: repoRoot,
    stdio: 'inherit',
  });
}

const isActive = runWpEnv(['plugin', 'is-active', pluginSlug]);

if (isActive.status === 0) {
  process.exit(0);
}

const activated = runWpEnv(['plugin', 'activate', pluginSlug]);
process.exit(activated.status ?? 1);
