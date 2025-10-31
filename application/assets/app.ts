import { registerReactControllerComponents } from '@symfony/ux-react';
import './bootstrap';
import './styles/app.css';

registerReactControllerComponents(require.context('./react/controllers', true, /\.[jt]sx?$/));
