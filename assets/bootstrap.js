import { startStimulusApp } from '@symfony/stimulus-bridge';

// Auto-register all controllers in assets/controllers
export const app = startStimulusApp(require.context(
  './controllers',
  true,
  /\.[jt]sx?$/
));
