/**
 * External dependencies
 */
var React = require( 'react' ),
	PropTypes = require( 'prop-types' ),
	throttle = require( 'lodash/throttle' );

/**
 * Internal dependencies
 */
var Label = require( './label' );

module.exports = React.createClass( {
	displayName: 'ModuleChartXAxis',

	propTypes: {
		labelWidth: PropTypes.number.isRequired,
		data: PropTypes.array.isRequired
	},

	getInitialState: function() {
		return {
			divisor: 1,
			spacing: this.props.labelWidth
		};
	},

	// Add listener for window resize
	componentDidMount: function() {
		this.resizeThrottled = throttle( this.resize, 400 );
		window.addEventListener( 'resize', this.resizeThrottled );
		this.resize();
	},

	// Remove listener
	componentWillUnmount: function() {
		if( this.resizeThrottled.cancel ) {
			this.resizeThrottled.cancel();
		}
		window.removeEventListener( 'resize', this.resizeThrottled );
	},

	componentWillReceiveProps: function( nextProps ) {
		this.resize( nextProps );
	},

	resize: function( nextProps ) {
		var node,
			props = this.props,
			width,
			dataCount,
			spacing,
			labelWidth,
			divisor;

		node = this.refs.axis;

		if ( nextProps && ! ( nextProps instanceof Event ) ) {
			props = nextProps;
		}

		/**
		 * Overflow needs to be hidden to calculate the desired width,
		 * but visible to display each labels' overflow :/
		 */

		node.style.overflow = 'hidden';
		width = node.clientWidth;
		node.style.overflow = 'visible';

		dataCount = props.data.length || 1;
		spacing = width / dataCount;
		labelWidth = props.labelWidth;
		divisor = Math.ceil( labelWidth / spacing );

		this.setState( {
			divisor: divisor,
			spacing: spacing
		} );
	},

	render: function() {
		var labels,
			data = this.props.data;

		labels = data.map( function ( item, index ) {
			var x = ( index * this.state.spacing ) + ( ( this.state.spacing - this.props.labelWidth ) / 2 ),
				rightIndex = data.length - index - 1,
				label;

			if ( rightIndex % this.state.divisor === 0 ) {
				label = <Label key={ index } label={ item.label } width={ this.props.labelWidth } x={ x } />;
			}

			return label;
		}, this );

		return (
			<div ref="axis" className="dops-chart__x-axis">{ labels }</div>
		);
	}
} );
