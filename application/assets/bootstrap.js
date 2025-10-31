import { startStimulusApp } from '@symfony/stimulus-bridge';

export const app = startStimulusApp(require.context('./react/controllers', true, /\.[jt]sx?$/));

// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
