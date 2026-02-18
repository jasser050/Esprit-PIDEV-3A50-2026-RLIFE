import { startStimulusApp } from '@symfony/stimulus-bridge';

// Registers Stimulus controllers from controllers.json and in the controllers/ directory
export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/
));
import { startStimulusApp } from '@symfony/stimulus-bridge';

// Auto-register all controllers in assets/controllers
export const app = startStimulusApp(require.context(
  './controllers',
  true,
  /\.[jt]sx?$/
));
