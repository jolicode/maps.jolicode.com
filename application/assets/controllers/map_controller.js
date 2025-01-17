import { Controller } from '@hotwired/stimulus';
import {default as maplibregl} from 'maplibre-gl';
import * as pmtiles from 'pmtiles';
import 'maplibre-gl/dist/maplibre-gl.min.css';

export default class extends Controller {
    static targets = [
        "availableStyles",
        "latitude",
        "longitude",
        "map",
        "saveStyle",
        "styleEditor",
        "styleUrl",
        "zoom",
    ];
    static values = {
        styleUrl: String,
        style: Object,
        region: { type: Object, default: {zoom: 15, longitude: 7.424450755119324, latitude: 43.738347784533} },
        popupFrozen: { type: Boolean, default: false },
    }

    initialize() {
        const hash = document.location.hash.substring(1);

        if (hash) {
            const parts = hash.split('/');

            if (parts.length === 3) {
                const [zoom, longitude, latitude] = parts;
                this.regionValue = {zoom: zoom, longitude: longitude, latitude: latitude};
            }
        }

        this.initializeMap();
    }

    popupFrozenValueChanged() {
        if (this.map) {
            this.popupFrozenValue ? this.map.getCanvas().style.cursor = 'default' : this.map.getCanvas().style.cursor = 'crosshair';
        }
    }

    regionValueChanged() {
        this.updateUrl();
    }

    styleUrlValueChanged() {
        const url = '/style/' + this.styleUrlValue + '.json';
        this.styleUrlTarget.setAttribute('href', url);
        this.styleUrlTarget.innerHTML = url;

        if (!this.map || !this.map.loaded()) {
            return;
        }

        this.updateUrl();

        fetch(url).then(async (response) => {
            this.styleValue = await response.json();
        });
    }

    styleValueChanged() {
        this.map && this.map.loaded() && this.map.setStyle(this.styleValue);
    }

    initializeMap() {
        // add the PMTiles plugin to the maplibregl global.
        const protocol = new pmtiles.Protocol();
        maplibregl.addProtocol('pmtiles', protocol.tile);

        // this is so we share one instance across the JS code and the map renderer
        protocol.add(new pmtiles.PMTiles( '/pmtiles/{{ location }}.pmtiles'));

        const url = '/style/' + this.styleUrlValue + '.json';

        fetch(url).then(async (response) => {
            this.styleValue = await response.json();
            this.map = new maplibregl.Map({
                container: this.mapTarget, // container id
                style: this.styleValue, // style URL
                center: [this.regionValue.longitude, this.regionValue.latitude], // starting position [lng, lat]
                zoom: this.regionValue.zoom, // starting zoom
                minZoom: 1,
                maxZoom: 19
            });
            this.map.getCanvas().style.cursor = 'crosshair';
            this.updateMeta();

            this.map.on('move', () => {
                this.updateMeta();
            });
            this.map.on('zoom', () => {
                this.updateMeta();
            });
            this.map.on('mousemove', (e) => {
                if (this.popupFrozenValue) {
                    return;
                }

                const { x, y } = e.point;
                const r = 2; // radius around the point
                let features = this.map.queryRenderedFeatures([
                    [x - r, y - r],
                    [x + r, y + r],
                ]);
                this.changeStyleEditor(features);
            });
            this.map.on('click', (e) => {
                this.popupFrozenValue = !this.popupFrozenValue;
            });
        });
    }

    changeStyleEditor(features) {
        let content = '';

        const foundStyles = {};

        features.map((feature) => {
            const currentStyle = this.styleValue.layers.find((layer) => layer.id === feature.layer.id);

            if (currentStyle && !foundStyles[currentStyle.id]) {
                foundStyles[currentStyle.id] = [currentStyle, feature];
            }
        });

        Object.keys(foundStyles).forEach((foundStyleKey) => {
            const [layer, feature] = foundStyles[foundStyleKey];
            content += `<details class="py-1 border-b border-gray-300"><summary><h2 class="inline">
                <span class="inline-flex items-center rounded-md bg-gray-400/10 px-1 text-xs font-normal text-gray-500 ring-1 ring-inset ring-gray-400/30">${feature.sourceLayer}</span>
                <span class="font-bold">${layer.id}</span>
            </h2></summary>`;

            if (Object.keys(feature.properties).length > 0) {
                content += '<table class="table-auto w-full my-2"><tbody>';
                Object.keys(feature.properties).forEach((key) => {
                    content += `<tr class="border-b border-gray-300">
                        <td>${key}</td>
                        <td>${feature.properties[key]}</td>
                    </tr>`;
                });
                content += '</tbody></table>';
            }

            content += `<pre
                contenteditable="true"
                class="mb-2 text-xs border border-gray-800 p-1 overflow-y-auto max-h-60"
                data-map-layer-id-param="${layer.id}"
                data-action="input->map#updateStyleFromStyleEditor"
            >${JSON.stringify(layer.paint, null, 2)}</pre>
            <details>
                <summary>Style details</summary>
                <pre>${JSON.stringify(layer, null, 2)}</pre>
            </details>
            <details>
                <summary>Feature details</summary>
                <pre>${JSON.stringify(feature, null, 2)}</pre>
            </details></details>`;
        });

        this.styleEditorTarget.innerHTML = content;
    }

    saveStyle() {
        const styleValue = this.styleValue;

        fetch('/style', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(styleValue),
        }).then(async (response) => {
            console.log(await response.json());
        });
    }

    updateStyleFromStyleEditor(event) {
        const styleValue = this.styleValue;
        let updated = false;

        styleValue.layers.forEach((layer, key) => {
            if (layer.id === event.params.layerId) {
                console.log(key);
                styleValue.layers[key].paint = JSON.parse(event.target.innerText);
                updated = true;
            }
        });

        if (updated) {
            this.styleValue = styleValue;
        }
    }

    updateUrl() {
        history.pushState({
            styleUrl: this.styleUrlValue,
            region: this.regionValue
        }, '', '/' + this.styleUrlValue + '#' + this.regionValue.zoom + '/' + this.regionValue.longitude + '/' + this.regionValue.latitude);
    }

    changeStyle(event) {
        this.styleUrlValue = event.params.styleUrl;
    }

    updateMeta() {
        let {lng, lat} = this.map.getCenter();
        this.regionValue = {zoom: this.map.getZoom().toFixed(2), longitude: lng, latitude: lat};

        this.latitudeTarget.innerHTML = lat.toFixed(2);
        this.longitudeTarget.innerHTML = lng.toFixed(2);
        this.zoomTarget.innerHTML = this.map.getZoom().toFixed(2);
    }
}
