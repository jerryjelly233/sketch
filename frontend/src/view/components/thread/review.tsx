import * as React from 'react';
import { Page } from '../common/page';
import { NavBar } from '../common/navbar';
import { Mark } from '../common/mark';
import './review.scss';
import { TextEditor } from '../common/textEditor';

export type RewardData = {
    title:string;
    brief:string;
    rate:number; // 评分, 1-10
    suggest:boolean; //向他人推荐
    body:string;
    indent:boolean; //首段缩进
};

interface Props {
    goBack:() => void;
    publish:(data:RewardData) => void;
    title:string;
}

interface State extends RewardData {
    publishDisabled:boolean;
}

export class Review extends React.Component<Props, State> {
    public state:State = {
        body: '',
        title: '',
        brief: '',
        rate: 10,
        suggest: false,
        indent: true,
        publishDisabled: true,
    };
    private textEditorRef = React.createRef<TextEditor>();

    public publish = () => {
        const ref = this.textEditorRef.current;
        if (!ref) {
            return;
        } else {
            const body = ref.getContent();
            if (body.trim().length == 0) { return; }
            this.props.publish({...this.state, body});
        }
        if (!this.textEditorRef.current) { return; }
        const messageToSend = this.textEditorRef.current.getContent();
        if (!messageToSend) { return; }
    }

    public getPushlishDisabled = () => {
        const ref = this.textEditorRef.current;
        const publishDisabled = !ref || ref.state.text.length == 0
            || ref.state.text == '<p><br></p>';
        // FIXME: empty state of text editor is p br p sometimes.
        if (publishDisabled != this.state.publishDisabled) {
            this.setState({publishDisabled});
        }
    }
    public render () {
        const { indent , title, brief, suggest } = this.state;
        return <Page top={
            <NavBar goBack={this.props.goBack}
                menu={NavBar.MenuText({
                    onClick: this.publish,
                    value: '发布',
                    disabled: this.state.publishDisabled,
                })}
            >
                评《{this.props.title}》
            </NavBar>
        }>
            <div className="review">
                <div className="section">
                    <div className="section-title">标题</div>
                    <div className="input-text">
                        <input type="text"
                            value={title}
                            onChange={(e) => this.setState({title: e.target.value})}
                            placeholder="选填，25字以内"/>
                    </div>
                </div>
                <div className="section">
                    <div className="section-title">概要</div>
                    <div className="input-text">
                        <input type="text"
                            value={brief}
                            onChange={(e) => this.setState({brief: e.target.value})}
                            placeholder="选填，40字以内"/>
                    </div>
                    <p className="left-margin small-warning">标题及概要中不得具有性描写、性暗示，不得使用直白的脏话、黄暴词和明显涉及边缘的词汇。</p>
                </div>
                <div className="section">
                    <div className="section-title">评分</div>
                    <Mark className="left-margin" length={5}
                        onClick={(v) => this.setState({rate: v * 2 })}/>
                    {/* TODO: use component checkbox */}
                    <span id="recommend-to-others">
                        <input
                            type="checkbox"
                            checked={suggest}
                            onChange={(e) => this.setState({suggest:e.target.checked})}/>
                        <label>向他人推荐</label>
                    </span>
                </div>
                <div className="section">
                    <div className="section-title">正文</div>
                    {/* TODO: cache unfinished review */}
                    <TextEditor
                        onChange={this.getPushlishDisabled}
                        ref={this.textEditorRef}
                        placeholder="为文章写评吧"/>
                    <div className="left-margin section-item">
                        <input
                            type="checkbox"
                            checked={indent}
                            onChange={() => this.setState({indent: !indent})} />
                        <label>段首缩进（每段前两个空格）</label>
                    </div>
                </div>
            </div>
        </Page>;
    }
}