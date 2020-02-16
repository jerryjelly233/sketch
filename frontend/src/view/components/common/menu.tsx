import * as React from 'react';
import { classnames } from '../../../utils/classname';
import { Card } from './card';
import { List } from './list';
import { Badge } from './badge';
const badgeStyle:React.CSSProperties = {float:'right'};

export function MenuItem (props:{
  onClick?:( params? ) => void,
  icon:string,
  badgeNum?:number,
  title:string,
}) {
  return (
    <List.Item onClick={props.onClick ?
      props.onClick :
      () => console.log('clicked')} arrow={true}>

      <span className="icon-with-right-text">
        <i className={props.icon} />
        <span>{props.title}</span>
      </span>

      { props.badgeNum && <Badge num={props.badgeNum} max={100} style={badgeStyle}/> }
    </List.Item>
  );
}

export function Menu (props:{
  className?:string;
  children:React.ReactNode;
}) {
  return (
    <Card>
      <List>
        {props.children}
      </List>
    </Card>);
}