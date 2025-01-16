import { Controller } from '@hotwired/stimulus';
import {default as maplibregl} from 'maplibre-gl';
import * as pmtiles from 'pmtiles';
import 'maplibre-gl/dist/maplibre-gl.min.css';

export default class extends Controller {
    static targets = ["map", "meta"];
    static values = {
        styleUrl: String,
    }

    initialize() {
        this.initializeMap();
    }

    initializeMap() {
        // add the PMTiles plugin to the maplibregl global.
        const protocol = new pmtiles.Protocol();
        maplibregl.addProtocol('pmtiles', protocol.tile);

        // this is so we share one instance across the JS code and the map renderer
        protocol.add(new pmtiles.PMTiles( '/pmtiles/{{ location }}.pmtiles'));

        fetch(this.styleUrlValue).then(async (response) => {
            const style = await response.json();
            this.map = new maplibregl.Map({
                container: this.mapTarget, // container id
                style: style, // style URL
                center: [7.424450755119324, 43.738347784533], // starting position [lng, lat]
                zoom: 15, // starting zoom
                minZoom: 1,
                maxZoom: 19
            });

            this.map.on('move', () => {
                this.updateMeta();
            });
            this.map.on('zoom', () => {
                this.updateMeta();

                const colors = [
                    '#ff0000',
                    '#ff8000',
                    '#ffff00',
                    '#80ff00',
                    '#00ff00',
                    '#00ff80',
                    '#00ffff',
                    '#0080ff',
                    '#0000ff',
                    '#8000ff',
                    '#ff00ff',
                    '#ff0080',
                ];

                style.layers[8].paint['fill-color'] = colors[Math.floor(Math.random() * colors.length)];
                style.layers[9].paint['fill-color'] = colors[Math.floor(Math.random() * colors.length)];
                this.map.setStyle(style);
            });
        });
    }

    updateMeta() {
        let {lng, lat} = this.map.getCenter();
        this.metaTarget.innerHTML = `<p>Latitude: ${lat.toFixed(2)}</p><p>Longitude: ${lng.toFixed(2)}</p><p>Zoom: ${this.map.getZoom().toFixed(2)}</p>`;
    }
}
