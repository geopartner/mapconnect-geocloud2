import React from 'react';
import styled from 'styled-components';

const StyledFooter = styled.div`
    width: 100%;
    text-align: center;
    padding: 15px 0;
    font-size: 12px;
    
    a {
        color: inherit;
        text-decoration: underline;
    }
`;

function Footer() {
    return (<StyledFooter>Powered by <a href='https://github.com/geopartner/mapconnect-geocloud2'>Mapconnect</a>, based on <a href='https://github.com/mapcentia/geocloud2'>GeoCloud2</a></StyledFooter>);
}

export default Footer;
