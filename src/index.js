import {registerPlugin} from "@wordpress/plugins";
import {PluginSidebar, PluginSidebarMoreMenuItem} from "@wordpress/edit-post";
import {__} from "@wordpress/i18n";
import {PanelBody, TextControl, ColorPicker} from "@wordpress/components";
import {withSelect, withDispatch} from "@wordpress/data";

import button from "@wordpress/components/build/button";

let PluginMetaFields = () => {

    let textColors = [];

    for (let i = 0; i < 4; i++) {
        textColors.push(<div style={{
            width: '50px',
            height: '50px',
            border: '1px solid black'
        }}></div>)
    }

    let backgroundColors = [];

    for (let i = 0; i < 4; i++) {
        backgroundColors.push(<div style={{
            width: '50px',
            height: '50px',
            border: '1px solid black'
        }}></div>)
    }

    let contrasts = [];

    for (let i = 0; i < 4; i++) {
        contrasts.push(
            <div style={{
                width: '50px',
                height: '50px',
                border: '1px solid black'
            }}></div>)
    }

    return (
        <div style={{display: 'flex', flexDirection: 'column', textAlign: 'center'}}>
            <button onClick={() => { console.log('Check Contrast') }}>Check Contrast</button>
            <hr/>
            <h2>Aanbevolen tekstkleuren</h2>
            <ul style={{display: 'flex', justifyContent: 'center', gap: '5px'}}>{textColors}</ul>
            <hr/>
            <h2>Aanbevolen achtergrondkleuren</h2>
            <ul style={{display: 'flex', justifyContent: 'center', gap: '5px'}}>{backgroundColors}</ul>
            <hr/>
            <h2>Aanbevolen kleurencombinaties</h2>
            <ul style={{display: 'flex', justifyContent: 'center', gap: '5px'}}>{contrasts}</ul>
            <hr/>
            <button onClick={() => { console.log('Pas Contrast Aan') }}>Pas Contrast Aan</button>
            
        </div>


    )

}

PluginMetaFields = withSelect(
    (select) => {
        return {
            text_metafield: select('core/editor').getEditedPostAttribute('meta')['_myprefix_text_metafield']
        }
    }
)(PluginMetaFields);

PluginMetaFields = withDispatch(
    (dispatch) => {
        return {
            onMetaFieldChange: (value) => {
                dispatch('core/editor').editPost({meta: {_myprefix_text_metafield: value}})
            }
        }
    }
)(PluginMetaFields);

registerPlugin('myprefix-sidebar', {
    icon: 'smiley',
    render: () => {
        return (
            <>
                <PluginSidebarMoreMenuItem
                    target="myprefix-sidebar"
                >
                    {__('Meta Options', 'textdomain')}
                </PluginSidebarMoreMenuItem>
                <PluginSidebar
                    name="myprefix-sidebar"
                    title={__('MissMatch', 'textdomain')}
                >
                    <PluginMetaFields/>
                </PluginSidebar>
            </>
        )
    }
})