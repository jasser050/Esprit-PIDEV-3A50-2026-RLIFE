import { startStimulusApp } from '@symfony/stimulus-bridge';
import CopingToolsController from './coping_tools_controller';

const app = startStimulusApp();
app.register('coping-tools', CopingToolsController);
