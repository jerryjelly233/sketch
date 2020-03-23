import * as React from 'react';
import './thread-profile.scss';
import { parseDate, isNewThread } from '../../../utils/date';
import { ResData } from '../../../config/api';
import { Card } from '../common/card';
import { TagList } from '../common/tag-list';
import { Tag } from '../common/tag';
import { Button } from '../common/button';
import { Colors } from '../../theme/theme';

export type ThreadMode = 'reading'|'discussion';

interface Props {
  thread:ResData.Thread;
  changeMode:(mode:ThreadMode) => void;
  onCollect:() => void;
  onReply:() => void;
  onReview:() => void;
  onReward:() => void;
}
interface State {
  rewardsExpanded:boolean;
}

export class ThreadProfile extends React.Component<Props, State> {
  public render () {
    const { thread } = this.props;
    const { attributes, author, tags } = thread;

    const rewardList:string[] = [];
    if (thread.recent_rewards[0]) {
      // FIXME: 我们好像这里对reward的datatype理解不同. 我如果没有理解错的话,每个reward只有一个author和receiver.
      // 并不是每个reward都有author或receiver, e.g. 对于自己发送的reward (author是自己),那么author为空. 对于自己收到的reward (receiver是自己), 那么receiver为空
      // 后端format object的方式很奇怪,是用array,再把array转成object. 这样一个问题就是,如果这个object是空的,那么得到的就是一个空的array. 很难区别后端是想返回一个空的object,还是空的array
      // 这里我暂时comment起来了 -03/19 Emol
      // thread.recent_rewards[0].author.forEach((author) => rewardList.push(author.attributes.name));
    }

    return <Card className="comps-thread-thread-profile">
      <div className="title-row">
        <div className="title">{attributes.title}
          <TagList>
            {(isNewThread(attributes.created_at)) && thread.last_component && thread.last_component.id &&
            <Tag size="tiny"><span className="primary-color">新</span></Tag>}
            {attributes.is_bianyuan && <Tag size="tiny">限</Tag>}
          </TagList>
        </div>
        <div className="author-name">{author.attributes.name}</div>
      </div>

      <div className="brief">{attributes.brief}</div>
      <div className="tags-row">{tags.map((tag) => tag.attributes.tag_name).join('-')}</div>
      <div className="data-row">
          <span>{parseDate(attributes.created_at)}/{parseDate(thread.last_post ? thread.last_post.attributes.created_at : undefined)}</span>
          <span>
            <i className="fa fa-eye"></i>
            {attributes.view_count || 0}
          </span>
          <span>
            <i className="fa fa-comment-alt"></i>
            {attributes.reply_count || 0}
          </span>
      </div>

      <div className="title">文案</div>
      <div className="body">{attributes.body}</div>

      <div className="title">最新章节</div>
      <div className="last-component">{thread.last_component && thread.last_component.attributes.title}</div>

      <div className="events">
        <Button type="ellipse" onClick={this.props.onCollect}>收藏{attributes.collection_count || ''}</Button>
        <Button type="ellipse" onClick={this.props.onReply}>回复</Button>
        <Button type="ellipse" onClick={this.props.onReview}>写评</Button>
        <Button type="ellipse" onClick={this.props.onReward}>打赏</Button>
      </div>

      {rewardList.length &&
        <div className="rewards-container">
          <div className="rewards">{rewardList.join(', ')}</div>
          <div className="expand" onClick={() => this.setState((prevState) => ({rewardsExpanded: !prevState.rewardsExpanded}))}>
            展开 <i className="fa fa-angle-down"></i>
          </div>
        </div>
      || ''}

      <div className="modes">
          <Button color={Colors.primary} onClick={() => this.props.changeMode('reading')}>阅读模式</Button>
          <Button color={Colors.primary} onClick={() => this.props.changeMode('discussion')}>讨论模式</Button>
      </div>
    </Card>;
  }
}