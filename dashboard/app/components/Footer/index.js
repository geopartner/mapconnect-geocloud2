import React from 'react';
import styled from 'styled-components';

const StyledFooter = styled.div`
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    text-align: center;
    padding: 15px 0;
    background-color: rgba(242, 242, 242, 0.9);
    border-top: 1px solid #ddd;
    z-index: 1000;
    
    a {
        color: inherit;
        text-decoration: underline;
    }
`;

function Footer() {
    return (<StyledFooter>Powered by <a href='https://github.com/geopartner/mapconnect-geocloud2'>Mapconnect</a>, based on <a href='https://github.com/mapcentia/geocloud2'>GeoCloud2</a></StyledFooter>);
}

export default Footer;
